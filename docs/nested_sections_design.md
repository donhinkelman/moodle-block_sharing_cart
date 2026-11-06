# Copying nested sections with the sharing cart

Design note for supporting course formats whose sections nest (for example
`format_pxgrid`) so that copying a section into the sharing cart also copies its
child sections, and inserting it into another course recreates that structure.

Status: under review in pull request #214. Nothing here is released yet.

## 1. Problem

Some course formats build a section tree on top of ordinary `course_sections`
rows. `format_pxgrid` keeps the tree in its own table
(`format_pxgrid_sections`: `section_id`, `parent_id`, `sort_order`). Moodle core
knows nothing about that tree.

Today the sharing cart copies one section as follows:

1. Run a full-course backup (`backup::TYPE_1COURSE`) and switch every section
   except the chosen one off via the `section_<id>_included` settings.
2. On insert, run a course restore (`TARGET_EXISTING_ADDING`) after rewriting
   `<number>` in every `section.xml` and `<sectionnumber>` in every `module.xml`
   to the number of the target section. Core therefore merges everything into
   that one section.

Child sections of a nested format are never included in the backup, and even if
they were, the restore would flatten them into the target section. The cart's
item tree and import modal would show nothing about them either.

## 2. Why this cannot live in the course format alone

All three decisions above are made by the cart:

- which sections go into the backup (settings applied inside the cart's adhoc
  task; the format's backup plugin only runs for included sections);
- where restored sections land (the XML rewrite happens before core, and hence
  before the format's restore plugin, sees anything);
- what the cart item tree and the import modal display.

A format could only work around this by sniffing backup controllers from
events and re-splitting merged sections afterwards. That fights explicit cart
policy and breaks on every cart change, so it is not an option.

## 3. Why hooks and not subplugins

Moodle 5.2 only allows subplugins under `mod`, `editor`, `tool` and `local`
(`core_component::$supportsubplugins`). A block cannot declare them.

The core hooks API is the supported alternative and fits better anyway: the cart
defines a few hook classes and dispatches them at well-defined points; a course
format registers callbacks in its own `db/hooks.php`. If the cart is not
installed the format's registration is simply never used, so the format gains no
hard dependency, and the cart stays free of any format-specific code.

## 4. Architecture overview

```
COPY (queue time, web request)
  section_into_sharing_cart  ->  backup\handler::backup_section
        |
        |  dispatch  block_sharing_cart\hook\backup\resolve_section_tree
        |            (format answers: "these sections are my descendants")
        v
  adhoc backup task custom data: backup_settings.section_tree = [...]

BACKUP (adhoc task)
  settings helper includes root + descendants (+ core subsections inside them)
  after backup: item repository builds nested cart items from the tree

INSERT (web request)
  item_into_section  ->  restore\handler  ->  adhoc restore task

RESTORE (adhoc task)
  plan sections   : root -> target number (merge), descendants -> fresh numbers
  dispatch        : hook\restore\before_sections_restored   (format may opt out
                    of its own hierarchy handling for this restore id)
  execute_plan()  : core creates the new sections
  resolve mapping : old section id -> new section id
  default placing : new sections moved directly after the target section
  dispatch        : hook\restore\after_sections_restored   (format attaches the
                    new sections to its tree)
```

## 5. The hooks

All hook classes live in `classes/hook/` of the cart and are plain value
objects. Payload fields are public readonly properties. Property names follow
the Moodle coding style (no underscores); the fields of the tree nodes inside
the lists are plain data and keep their snake_case names.

### `backup\resolve_section_tree`

Dispatched synchronously when a section is added to the cart.

- Input: `courseid`, `sectionid` (the copied section).
- Callbacks call `add_child(sectionid, parentsectionid, sortorder)` for
  every descendant, any depth.
- `get_tree()` returns the descendants depth-first (parents before children,
  siblings by `sort_order`), root excluded, unreachable nodes dropped.

### `restore\before_sections_restored`

Dispatched inside the restore adhoc task, before `execute_plan()`.

- `restoreid`, `courseid`, `targetsectionid`.
- `plannedsections`: depth-first list of
  `{old_section_id, old_parent_section_id, sort_order, new_section_number}`.
- Purpose: let a format mark this restore as "structure managed by the cart" so
  its own format restore plugin does not try to merge or reposition sections.

### `restore\after_sections_restored`

Dispatched after `execute_plan()`, before the controller is destroyed.

- `courseid`, `targetsectionid` (the section the root merged into).
- `restoredsections`: depth-first list of
  `{old_section_id, new_section_id, new_parent_section_id, sort_order}`.
  For first-level children `new_parent_section_id` is the target section.
- Purpose: let a format write its own hierarchy rows for the new sections.

## 6. Backup side

- Whether a section can be copied at all is decided server side, after the tree
  is resolved: a section without activities is still copyable when one of its
  declared descendants has activities (a structural parent that only holds
  subsections). An empty section with no populated descendants is rejected with
  the existing "no course modules in this section" message.

- `app\backup\handler::backup_section` dispatches `resolve_section_tree` and
  stores the answer in the task custom data as `backup_settings.section_tree`.
- `app\backup\backup_settings_helper` works on a set of section ids (root plus
  tree) instead of a single id: those sections, their activities, and any core
  `mod_subsection` delegated sections inside them are included; everything else
  stays excluded. A plain section is just a tree of one, so existing behaviour
  is unchanged.
- `app\backup\handler::get_backup_item_tree` no longer assumes a single section.
  It reads all non-delegated sections from `moodle_backup.xml`, orders them by
  the stored tree, attaches core subsections to the section that owns their
  parent module, and attaches activities by section id.
- `app\item\repository::update_sharing_cart_item_with_backup_file` builds the
  cart items: descendant sections become items of `type = 'section'` whose
  `parent_item_id` points at the parent section item and whose `sortorder` is
  the tree order; activities hang under their section item; core subsections
  keep today's `mod_subsection` shape.

Item tree after copying a pxgrid section with two levels of subsections:

```
section (root, has the backup file)
  mod_page
  section            <- pxgrid subsection
    mod_forum
    section          <- pxgrid sub-subsection
      mod_quiz
  section
    mod_subsection   <- core subsection stays as before
      mod_label
```

## 7. Restore side

The restored item can be the root item or any child section item. The subtree
is read from the cart items table, not from the backup file. The adhoc task
only orchestrates; the decisions live in `app\restore\section_planner`
(producing a `section_plan`) and `app\restore\section_details_replacement`.

Numbering algorithm (`section_planner`):

1. The restored item's own section gets the target section's number in
   `section.xml`, so core reuses (merges into) the target. `module.xml` numbers
   are rewritten only for modules of that section.
2. Each descendant, in depth-first order, gets a fresh number:
   `MAX(section)` over all rows of the target course (delegated included)
   plus 1, 2, 3 ... Core creates a new row at that number and moves any
   delegated sections above it, so the numbers never collide.
3. Every other non-delegated section task in the backup is switched off via
   its `included` setting; its activities follow through setting dependencies.
   Delegated sections whose parent module sits in an excluded section are
   switched off as well.
4. After the plan runs, old ids are mapped to new ids from the section tasks
   themselves: each `restore_section_task` still holds the id of the section it
   created or merged into. The `backup_ids` mapping table cannot be used here
   because the last restore step drops it.
5. Generic default placement: the new sections are moved directly after the
   target section in depth-first order using
   `core_courseformat\formatactions::section()->move_after()`. Only then is
   `after_sections_restored` dispatched.

Implementation notes for whoever touches the restore task:

- `restore_task::get_info()` returns the whole backup manifest, not the task's
  own entry. The original section id of a section task is taken from its
  directory name (`sections/section_<oldid>`); the original section of an
  activity task comes from the manifest's module list via `get_old_moduleid()`.
- Never call `set_status()` on the controller after `execute_plan()` from new
  code paths: it checksums the fully built plan, which recurses without bound.
  The existing catch block in the task does this today and turns any restore
  exception into a memory exhaustion; that is a pre-existing issue worth fixing
  separately.
- A nested pxgrid section is appended after the parent's existing children by
  using the highest existing sort order plus one; the format's own
  "next sort order" helper fills the first gap instead and would place the copy
  first.

Degraded mode: if the target course's format registers no callback, content is
still complete and the copied subsections sit as flat sections right after the
target. Formats that nest simply attach them.

### Replacing the target's title and description on merge

Core only writes the copied section's title and description into a target
whose fields are empty, so a merge silently kept the existing text. The import
modal now offers "Also replace the title and description of X with those of the
copied section" whenever the target has a title or description, and the web
service accepts `replacesectiondetails`. `section_details_replacement`
implements it by keeping the target's title, description and description files
in a snapshot (file area `block_sharing_cart/section_snapshot`, item id = the
section), then blanking them right before the plan runs so core fills them from
the backup exactly as for a new section, files included. When the restore
finishes the snapshot is discarded; when it fails the snapshot is put back, so a
failed restore never leaves the section without its original text. The option
is not shown when inserting as a new section, where the copied values are used
anyway.

### Insert as a new section (front page)

The insert web service also accepts `insertasnewsection` and, for the top
level of a course, `sectionid = 0` together with `courseid`. In that mode the
copied section itself is planned like a descendant: it gets a fresh number, core
creates it from the backup (name, summary, visibility included), and it becomes
the parent of its own subtree. `after_sections_restored` then carries the root
too, with `new_parent_section_id` equal to the target section, or `0` for the
top level. Only section items may use this mode.

A course format offers this by rendering a placeholder wherever a "new section
here" drop target makes sense:

```html
<div data-region="sharing-cart-course-target"
     data-course-id="{{course_id}}" data-parent-section-id="{{parent_or_0}}"></div>
```

The block script fills the placeholder with the usual clipboard target while a
section item is on the clipboard. pxgrid renders it under its card grid: on the
front page with parent `0`, on a section page with that section as parent, so a
copied section can also be dropped in as a new subsection.

The optional `sectionstoinclude` parameter of the insert web service lets the
import modal drop whole branches; an excluded section drops its subtree.

## 8. User interface

- The block item template already recurses over `children`, and the collapse
  logic already treats `type = section` at any depth, so nested section items
  render as folders within folders without changes.
- The import modal is built from the cart item subtree instead of the backup
  file and renders nested sections recursively. Section checkboxes carry
  `data-type="section"`; the block JavaScript sends them as
  `sectionstoinclude` and everything else as `coursemodulestoinclude`.
- Drop targets: a section item (root or nested) can be inserted into any regular
  section but not into a core subsection, the same rule that applies to root
  sections today.
- After a restore finishes, the block refreshes the whole course editor state
  (`courseState`) instead of only the target section. A restore can create
  sections the client does not know yet; refreshing only the target left
  pxgrid's card list unable to sort them, which showed newly created cards
  behind the "add section" card until the page was reloaded.

## 9. What a nesting format implements (pxgrid as the example)

`format_pxgrid` registers three static callbacks in `db/hooks.php`
(`format_pxgrid\hook_callbacks`):

- `resolve_section_tree`: depth-first walk of `format_pxgrid_sections` from the
  copied section, skipping delegated rows, only when the course format is
  pxgrid.
- `before_sections_restored`: records the restore id on the format's restore
  plugin. `process_level_data()` still restores section images but stops
  buffering hierarchy data for that restore, so the positional merge that the
  plugin performs for whole-course restores never runs.
- `after_sections_restored`: inserts or updates a `format_pxgrid_sections` row
  per restored section (parent = `new_parent_section_id`, sort order appended
  after the parent's existing children), then runs the format's ordering,
  invalidates the section image cache and rebuilds the course cache.

## 10. Backwards compatibility

- Items copied before this change have no nested section items and restore
  exactly as before.
- `mod_subsection` (core) items and single activity items keep the legacy
  restore path untouched.
- Backup file format is unchanged; the cart's `version` column is not bumped.

## 11. Testing

- Cart PHPUnit: settings helper with a `section_tree`; backup task with a
  redirected `resolve_section_tree` hook producing nested items; restore task
  creating descendant sections after the target and delivering the
  `after_sections_restored` payload; restore validity rules for child items.
- pxgrid PHPUnit (skipped when the cart is absent): copy a nested section from
  course A, restore into course B, assert the `format_pxgrid_sections` parent
  chain, sort order and activity placement, and that an existing subsection at
  the same position in B is left alone.
- Behat: copy a nested pxgrid section, verify folders within folders in the
  block and the modal, insert into another course, verify the course page.

## 12. Release checklist (not done for the POC)

- Cart: `version.php` bump, README change log entry, lang strings if the modal
  gains new text, `grunt amd` build of the block JavaScript.
- pxgrid: `version.php` bump and README release note; mention the minimum cart
  release that ships the hooks.
- Praxis workflow: cart pull request first, pxgrid pull request references the
  hook class names.

## 13. Open questions

- Merging into a target section and inserting as a new section are both
  supported now; the UI offers merge on section content lists and "new section"
  on the format's placeholders. Whether merge should remain the default for
  section lists is a product decision.
- Should the generic default placement be skipped when a format callback is
  registered? Currently both run; formats that renumber sections themselves
  simply overwrite the placement.
