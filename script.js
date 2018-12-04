// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 *  Sharing Cart
 *
 *  @package    block_sharing_cart
 *  @copyright  2017 (C) VERSION2, INC.
 *  @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require(['jquery'], function ($)
{
    $(document).ready(function()
    {
        /** @var {Object}  The icon configurations */
        var icon = {
            // actions
            'backup'  : { css: 'editing_backup' , pix: 'i/backup'  },
            'movedir' : { css: 'editing_right'  , pix: 't/right'   },
            'move'    : { css: 'editing_move_'  , pix: 't/move'    },
            'edit'    : { css: 'editing_update' , pix: 't/edit'    },
            'cancel'  : { css: 'editing_cancel' , pix: 't/delete'  },
            'delete'  : { css: 'editing_update' , pix: 't/delete'  },
            'restore' : { css: 'editing_restore', pix: 'i/restore' },
            // directories
            'dir-open'   : { pix: 'f/folder-open'   },
            'dir-closed' : { pix: 'f/folder' }
        };

        /** @var {Node}  The Sharing Cart block container node */
        var $block = $('.block_sharing_cart');

        var $spinner_modal = {
            show: function()
            {
                $('#sharing-cart-spinner-modal').show();
            },
            hide: function()
            {
                $('#sharing-cart-spinner-modal').hide();
            }
        };

        /** @var {Object}  The current course */
        var course = new function ()
        {
            var body = $('body');
            this.id = body.attr('class').match(/course-(\d+)/)[1];
            this.is_frontpage = body.hasClass('pagelayout-frontpage');
        };

        /**
         *  Returns a localized string
         *
         *  @param {String} identifier
         *  @return {String}
         */
        function str(identifier)
        {
            return M.str.block_sharing_cart[identifier] || M.str.moodle[identifier];
        }

        /**
         *  Shows an error message with given Ajax error
         *
         *  @param {Object} response  The Ajax response
         */
        function show_error(response)
        {
            try
            {
                var ex = JSON.parse(response.responseText);
                new M.core.exception({
                    name: str('pluginname') + ' - ' + str('error'),
                    message: ex.message
                });
            }
            catch (e)
            {
                new M.core.exception({
                    name: str('pluginname') + ' - ' + str('error'),
                    message: response.responseText
                });
            }
        }

        /**
         *  Get an action URL
         *
         *  @param {String} name   The action name
         *  @param {Object} [args] The action parameters
         *  @return {String}
         */
        function get_action_url(name, args)
        {
            var url = M.cfg.wwwroot + '/blocks/sharing_cart/' + name + '.php';
            if (args)
            {
                var q = [];
                for (var k in args)
                {
                    q.push(k + '=' + encodeURIComponent(args[k]));
                }
                url += '?' + q.join('&');
            }
            return url;
        }

        /**
         *  Check special layout (theme boost)
         *
         *  @return {Boolean}
         */
        function verify_layout()
        {
            var menuelement = $block.find('.menubar .dropdown .dropdown-menu');
            return (menuelement.length);
        }

        /**
         * Set Cookie
         * @param name
         * @param value
         * @param expireTimeInMillisecond
         */
        function setCookie(name, value, expireTimeInMillisecond)
        {
            var d = new Date();
            d.setTime(d.getTime() + expireTimeInMillisecond);
            var expires = 'expires=' + d.toUTCString();
            document.cookie = name + '=' + value + ';' + expires + '';
        }

        /**
         * Get Cookie Value
         * @param param
         * @returns {*}
         */
        function getCookieValue(param)
        {
            var readCookie = document.cookie.match('(^|;)\\s*' + param + '\\s*=\\s*([^;]+)');
            return readCookie ? readCookie.pop() : '';
        }

        /**
         * Create a command icon
         *
         *  @param {String} name  The command name, predefined in icon
         *  @param {String} [pix] The icon pix name to override
         */
        function create_command(name, pix)
        {
            var imageelement = $('<img class="iconsmall "/>')
                .attr('alt', str(name))
                .attr('src', M.util.image_url(pix || icon[name].pix));
            if (verify_layout())
            {
                imageelement.addClass('iconcustom');
            }

            return $('<a href="javascript:void(0)"/>')
                .addClass(icon[name].css)
                .attr('title', str(name))
                .append(imageelement);
        }

        /**
         *  Create a command icon for moodle 3.2
         *
         *  @param {String} name  The command name, predefined in icon
         *  @param {String} [pix] The icon pix name to override
         */
        function create_special_activity_command(name, pix)
        {
            return $('<a href="javascript:void(0)"/>')
                .addClass(icon[name].css)
                .addClass('dropdown-item menu-action cm-edit-action')
                .attr('title', str(name))
                .append(
                    $('<img class="icon"/>')
                        .attr('alt', str(name))
                        .attr('src', M.util.image_url(pix || icon[name].pix))
                );
        }

        /**
         * Create a spinner
         * @param $node
         * @returns {*|jQuery}
         */
        function add_spinner($node)
        {
            var WAITICON = {'pix':"i/loading_small",'component':'moodle'};

            if($node.find(".spinner").length)
            {
                return $node.find(".spinner");
            }

            var spinner = $("<img/>").attr("src", M.util.image_url(WAITICON.pix, WAITICON.component))
                .addClass("spinner iconsmall")
                .hide();

            $node.append(spinner);
            return spinner;
        }

        /**
         *  Reload the Sharing Cart item tree
         */
        function reload_tree()
        {
            var $spinner = add_spinner($block.find('.commands'));

            $spinner.show();

            $.post(get_action_url("rest"),
                {
                    "action": "render_tree"
                },
            function(response)
            {
                $block.find(".tree").replaceWith($(response));
                $.init_item_tree();
            }, "text")
                .fail(function(response)
                {
                    show_error(response);
                })
                .always(function(response)
                {
                    $spinner.hide();
                });
        }

        /**
         *  Backup an activity
         *
         *  @param {int} cmid
         *  @param {Boolean} userdata
         */
        function backup(cmid, userdata)
        {
            var $commands = $('#module-' + cmid + ' .commands');
            if(!$commands.length)
            {
                $commands = $('[data-owner="#module-' + cmid + '"]');
            }

            $spinner_modal.show();

            $.post(get_action_url("rest"),
                {
                    "action": "backup",
                    "cmid": cmid,
                    "userdata": userdata,
                    "sesskey": M.cfg.sesskey,
                    "course": course.id
                },
            function()
            {
                reload_tree();
            })
                .fail(function(response)
                {
                    show_error(response);
                })
                .always(function(response)
                {
                    $spinner_modal.hide();
                });
        }

        /**
         *  Backup an activities in a section
         *
         *  @param {int} sectionID
         *  @param {Boolean} userdata
         */
        function backup_section(sectionID, userdata)
        {
            var $commands = $('span.inplaceeditable[data-itemtype=sectionname][data-itemid=' + sectionID + ']');
            var sectionName = $commands.closest("li.section.main").attr('aria-label');

            var $spinner = add_spinner($commands);

            $spinner_modal.show();

            $.post(get_action_url("rest"),
                {
                    "action": "backup_section",
                    "sectionid": sectionID,
                    "sectionname": sectionName,
                    "userdata": userdata,
                    "sesskey": M.cfg.sesskey,
                    "course": course.id
                },
                function()
                {
                    reload_tree();
                })
                .fail(function(response)
                {
                    show_error(response);
                })
                .always(function(response)
                {
                    $spinner_modal.hide();
                });
        }


        ///////// CLASSES /////////

        /**
         *  @class Directory states manager
         */
        var directories = new function ()
        {
            var KEY = 'block_sharing_cart-dirs';

            var opens = getCookieValue(KEY).split(',').map(function (v) { return parseInt(v); });

            function save()
            {
                var expires = new Date();
                expires.setDate(expires.getDate() + 30);
                setCookie(KEY, opens.join(','), expires);
            }
            function open($dir, visible)
            {
                var pix = icon[visible ? 'dir-open' : 'dir-closed'].pix;
                $dir.find('> div img').first().attr('src', M.util.image_url(pix));
                $dir.find('> ul.list')[visible ? 'show' : 'hide']();
            }
            function toggle(e)
            {
                var $dir = $(e.target).closest('li.directory');
                var i = $dir.attr('id').match(/(\d+)$/)[1];
                var v = $dir.find('> ul.list').css('display') === 'none';

                open($dir, v);
                opens[i] = v ? 1 : 0;
                save();
            }

            /**
             *  Initialize directory states
             */
            this.init = function ()
            {
                var i = 0;
                $block.find('li.directory').each(function (index, dir)
                {
                    var $dir = $(dir);
                    $dir.attr('id', 'block_sharing_cart-dir-' + i);
                    if (i >= opens.length)
                    {
                        opens.push(0);
                    }
                    else if (opens[i])
                    {
                        open($dir, true);
                    }
                    $dir.find('> div').css('cursor', 'pointer').click(function(e)
                    {
                        toggle(e);
                    });
                    i++;
                });
            };
            /**
             *  Reset directory states
             */
            this.reset = function ()
            {
                opens = [];
                this.init();
                save();
            };
        };

        /**
         *  @class Targets for moving an item directory
         */
        var move_targets = new function ()
        {
            var $cancel = null, targets = [];

            /**
             *  Hide move targets
             */
            this.hide = function ()
            {
                if ($cancel !== null)
                {
                    var $commands = $cancel.closest('.commands');
                    $cancel.remove();
                    $cancel = null;
                    $commands.closest('li.activity').css('opacity', 1.0);
                    $commands.find('a').each(function () { $(this).show(); });
                    $.each(targets, function (index, $target) { $target.remove(); });
                    targets = [];
                }
            };

            /**
             *  Show move targets for a given item
             *
             *  @param {int} id  The item ID
             */
            this.show = function (id)
            {
                this.hide();

                function move(e)
                {
                    var m = $(e.target).closest('a').attr('class').match(/move-(\d+)-to-(\d+)/);
                    var id = m[1], to = m[2];

                    $.post(get_action_url("rest"),
                        {
                            "action": "move",
                            "id": id,
                            "to": to,
                            "sesskey": M.cfg.sesskey
                        },
                    function()
                    {
                        reload_tree();
                    })
                        .fail(function(response)
                        {
                            show_error(response);
                        });
                }

                var $current = $block.find('#block_sharing_cart-item-' + id);
                var $indent = $current.find('div');
                var $next = $current.next();
                var $list = $current.closest('ul');

                var next_id = 0;
                if($next.length)
                {
                    next_id = $next.attr('id').match(/item-(\d+)$/)[1];
                }

                function create_target(id, to)
                {
                    var $anchor = $('<a href="javascript:void(0)"/>')
                        .addClass('move-' + id + '-to-' + to)
                        .attr('title', str('movehere'))
                        .append(
                            $('<img class="move_target"/>')
                                .attr('alt', str('movehere'))
                                .attr('src', M.util.image_url('movehere'))
                        );

                    var $target = $('<li class="activity"/>')
                        .append($($indent[0].cloneNode(false)).append($anchor));
                    $anchor.click(function(e) { move(e) });

                    return $target;
                }

                $list.find('> li.activity').each(function (index, item)
                {
                    var $item = $(item);
                    var to = $item.attr('id').match(/item-(\d+)$/)[1];
                    if (to === id)
                    {
                        $cancel = create_command('cancel', 't/left');
                        $cancel.click(function()
                        {
                            move_targets.hide();
                        });
                        var $commands = $item.find('.commands');
                        $commands.find('a').each(function () { $(this).hide(); });
                        $commands.append($cancel);
                        $item.css('opacity', 0.5);
                    }
                    else if (to !== next_id)
                    {
                        var $target = create_target(id, to);
                        $item.before($target);
                        targets.push($target);
                    }
                }, this);

                if ($next)
                {
                    var $target = create_target(id, 0);
                    $list.append($target);
                    targets.push($target);
                }
            }
        };

        /**
         *  @class Targets for restoring an item
         */
        var restore_targets = new function ()
        {
            this.is_directory = null;
            var $clipboard = null, targets = [];

            function create_target(id, section)
            {
                var href = '';
                if(restore_targets.is_directory)
                {
                    href = get_action_url('restore', {
                        'directory': true,
                        'path': id,
                        'course': course.id,
                        'section': section,
                        'sesskey': M.cfg.sesskey
                    });
                }
                else
                {
                    href = get_action_url('restore', {
                        'directory': false,
                        'id': id,
                        'course': course.id,
                        'section': section,
                        'sesskey': M.cfg.sesskey
                    });
                }

                var $target = $('<a/>')
                    .attr('href', href)
                    .attr('title', str('copyhere'))
                    .append(
                        $('<img class="move_target"/>')
                            .attr('alt', str('copyhere'))
                            .attr('src', M.util.image_url('movehere'))
                    );

                targets.push($target);
                return $target;
            }

            /**
             *  Hide restore targets
             */
            this.hide = function ()
            {
                if ($clipboard !== null)
                {
                    $clipboard.remove();
                    $clipboard = null;
                    $.each(targets, function (index, $target) { $target.remove(); });
                    targets = [];
                }
            };

            /**
             *  Show restore targets for a given item
             *
             *  @param {int} id  The item ID
             */
            this.show = function (id)
            {
                this.hide();

                var $view = $("<span/>");

                if(this.is_directory)
                {
                    $view.html(id).css('display', 'inline');
                    $view.prepend(
                        $("<img/>").addClass("icon")
                            .attr("alt", id)
                            .attr("src", M.util.image_url(icon['dir-closed'].pix, null))
                    );
                }
                else
                {
                    var $item = $block.find('#block_sharing_cart-item-' + id);
                    $view = $($item.find('div')[0].cloneNode(true)).css('display', 'inline');
                    $view.attr('class', $view.attr('class').replace(/mod-indent-\d+/, ''));
                    $view.find('.commands').remove();
                }

                var $cancel = create_command('cancel');
                $cancel.click(this.hide);

                $clipboard = $('<div class="clipboard"/>');
                $clipboard.append(str('clipboard') + ": ").append($view).append($cancel);

                if (course.is_frontpage)
                {
                    var $sitetopic = $('.sitetopic');
                    var $mainmenu = $('.block_site_main_menu');
                    if ($sitetopic)
                    {
                        $sitetopic.find('*').before($clipboard);
                    }
                    else if ($mainmenu)
                    {
                        $mainmenu.find('.content').before($clipboard);
                    }

                    // mainmenu = section #0, sitetopic = section #1
                    if ($mainmenu)
                    {
                        $mainmenu.find('.footer').before(create_target(id, 0));
                    }
                    if ($sitetopic)
                    {
                        $sitetopic.find('ul.section').append(create_target(id, 1));
                    }
                }
                else
                {
                    var $container = $('.course-content');
                    $container.one('*').before($clipboard);
                    $container.find(M.course.format.get_section_wrapper(null)).each(function (index, sectionDOM)
                    {
                        var $section = $(sectionDOM);
                        var section = $section.attr('id').match(/(\d+)$/)[1];
                        $section.find('ul.section').append(create_target(id, section));
                    }, this);
                }
            }
        };

        ///////// INITIALIZATION /////////

        $.get_plugin_name = function()
        {
            var $blockheader = $block.find("h2");

            if(!$blockheader.length)
            {
                $blockheader = $block.find("h3");

                if($blockheader.length)
                {
                    return $blockheader.html();
                }
            }
            else
            {
                return $blockheader.html();
            }

            return "";
        };

        $.on_backup = function (e)
        {
            var cmid = (function ($backup)
            {
                var $activity = $backup.closest('li.activity');
                if ($activity.length)
                {
                    return $activity.attr('id').match(/(\d+)$/)[1];
                }
                var $commands = $backup.closest('.commands');
                var dataowner = $commands.attr('data-owner');
                if (dataowner.length)
                {
                    return dataowner.match(/(\d+)$/)[1];
                }
                return $commands.find('a.editing_delete').attr('href').match(/delete=(\d+)/)[1];
            })($(e.target));

            (function (on_success)
            {
                $.post(get_action_url('rest'),
                    {
                        "action": "is_userdata_copyable",
                        "cmid": cmid
                    },
                function(response)
                {
                    on_success(response);
                }, "text")
                    .fail(function(response)
                    {
                       show_error(response);
                    });
            })(function (response)
            {
                function embed_cmid(cmid)
                {
                    return '<!-- #cmid=' + cmid + ' -->';
                }

                function parse_cmid(question)
                {
                    return /#cmid=(\d+)/.exec(question)[1];
                }

                var copyable = response === '1';
                if (copyable)
                {
                    if(confirm(str('confirm_userdata')))
                    {
                        if(confirm(str('confirm_backup')))
                        {
                            backup(cmid, true);
                        }
                    }
                    else
                    {
                        if(confirm(str('confirm_backup')))
                        {
                            backup(cmid, false);
                        }
                    }
                }
                else
                {
                    if(confirm(str('confirm_backup')))
                    {
                        backup(cmid, false);
                    }
                }
            });
        };

        /**
         *  On movedir command clicked
         *
         *  @param {DOMEventFacade} e
         */
        $.on_movedir = function (e)
        {
            var $commands = $(e.target).closest('.commands');

            var $current_dir = $commands.closest('li.directory');
            var current_path = $current_dir.length ? $current_dir.attr('directory-path') : '/';

            var id = $(e.target).closest('li.activity').attr('id').match(/(\d+)$/)[1];

            var dirs = [];
            $block.find('li.directory').each(function ()
            {
                dirs.push($(this).attr('directory-path'));
            });

            var $form = $('<form/>').css('display', 'inline');
            $form.attr('action', 'javascript:void(0)');

            function submit()
            {
                var to = $form.find('[name="to"]').val();
                $.post(get_action_url('rest'),
                    {
                        "action": "movedir",
                        "id": id,
                        "to": to,
                        "sesskey": M.cfg.sesskey
                    },
                function()
                {
                    reload_tree();
                    directories.reset();
                })
                    .fail(function(response)
                    {
                        show_error(response);
                    });
            }

            $form.submit(submit);

            if (dirs.length === 0)
            {
                $form.append($('<input type="text" name="to"/>').val(current_path));
            }
            else
            {
                dirs.unshift('/');

                var $select = $('<select name="to"/>');
                for (var i = 0; i < dirs.length; i++)
                {
                    $select.append($('<option/>').val(dirs[i]).append(dirs[i]));
                }
                $select.val(current_path);
                $select.change(submit);
                $form.append($select);

                var $edit = create_command('edit');

                $edit.click(function (e)
                {
                    var $input = $('<input type="text" name="to"/>').val(current_path);
                    $select.remove();
                    $edit.replaceWith($input);
                    $input.focus();
                });

                $form.append($edit);
            }

            var $cancel = create_command('cancel');
            $cancel.click(function ()
            {
                $form.remove();
                $commands.find('a').show();
            });
            $form.append($cancel);

            $commands.find('a').each(function () { $(this).hide(); });
            $commands.append($form);
        };

        /**
         *  On move command clicked
         *
         *  @param {DOMEventFacade} e
         */
        $.on_move = function (e)
        {
            var $item = $(e.target).closest('li.activity');
            var id = $item.attr('id').match(/(\d+)$/)[1];

            move_targets.show(id);
        };

        /**
         *  On delete command clicked
         *
         *  @param {DOMEventFacade} e
         */
        $.on_delete = function (e)
        {
            if (!confirm(str('confirm_delete')))
            {
                return;
            }

            var $item = $(e.target).closest('li');
            var data = {};

            if($item.hasClass("directory"))
            {
                data = {
                    "action": "delete_directory",
                    "path": $item.attr("directory-path"),
                    "sesskey": M.cfg.sesskey
                };
            }
            else if($item.hasClass("activity"))
            {
                data = {
                    "action": "delete",
                    "id": $item.attr('id').match(/(\d+)$/)[1],
                    "sesskey": M.cfg.sesskey
                };
            }

            var $spinner = add_spinner($(e.target).closest('.commands'));

            $spinner.show();

            $.post(get_action_url("rest"), data,
            function()
            {
                reload_tree();
            })
                .fail(function(response)
                {
                    show_error(response);
                })
                .always(function()
                {
                    $spinner.hide();
                });

            e.stopPropagation();
        };

        /**
         *  On restore command clicked
         *
         *  @param {DOMEventFacade} e
         */
        $.on_restore = function (e)
        {
            var $item = $(e.target).closest('li');
            var id = null;

            if($item.hasClass("directory"))
            {
                id = $item.attr("directory-path");
                restore_targets.is_directory = true;
            }
            else if($item.hasClass("activity"))
            {
                id = $item.attr('id').match(/(\d+)$/)[1];
                restore_targets.is_directory = false;
            }

            restore_targets.show(id);
        };

        /**
         * On backup the whole section as a folder
         * @param {int} sectionID
         */
        $.on_section_backup = function (sectionID)
        {
            (function (on_success)
            {
                $.post(get_action_url('rest'),
                    {
                        "action": "is_userdata_copyable_section",
                        "sectionid": sectionID
                    },
                    function(response)
                    {
                        on_success(response);
                    }, "text")
                    .fail(function(response)
                    {
                        show_error(response);
                    });
            })(function (response)
            {
                var copyable = response === '1';
                if (copyable)
                {
                    if(confirm(str('confirm_userdata_section')))
                    {
                        if(confirm(str('confirm_backup_section')))
                        {
                            backup_section(sectionID, true);
                        }
                    }
                    else
                    {
                        if(confirm(str('confirm_backup_section')))
                        {
                            backup_section(sectionID, false);
                        }
                    }
                }
                else
                {
                    if(confirm(str('confirm_backup_section')))
                    {
                        backup_section(sectionID, false);
                    }
                }
            });
        };

        /**
         *  Initialize the delete bulk
         */
        $.init_bulk_delete = function (isspeciallayout)
        {
            var bulkdelete = $block.find('.header-commands .editing_bulkdelete');

            if (bulkdelete.length)
            {
                if (isspeciallayout)
                {
                    bulkdelete.attr('role', 'menuitem').addClass('dropdown-item menu-action');
                    bulkdelete.find('img').addClass('icon');

                    bulkdelete.append($("<span class='menu-action-text'/>").append(bulkdelete.attr('title')));

                    $block.find('.menubar .dropdown .dropdown-menu').append(bulkdelete);
                }
                else
                {
                    $block.find('.header .commands').append(bulkdelete);
                }
            }
        };

        /**
         *  Initialize the help icon
         */
        $.init_help_icon = function (isspeciallayout)
        {
            var helpicon = $block.find('.header-commands > .help-icon');

            if (isspeciallayout)
            {
                helpicon.attr('data-placement', 'left').find('.help-icon')
                    .prepend($('<span/>').append(M.str.block_sharing_cart['pluginname']).addClass('sc-space-5'));
                $block.find('.header-commands').parent().css('display', 'block');
            }
            else
            {
                $block.find('.header .commands').append(helpicon);
            }
        };

        /**
         *  Initialize the Sharing Cart block header
         */
        $.init_block_header = function()
        {
            var isspeciallayout = verify_layout();
            $.init_bulk_delete(isspeciallayout);
            $.init_help_icon(isspeciallayout);
        };

        /**
         *  Initialize the Sharing Cart item tree
         */
        $.init_item_tree = function()
        {
            function add_actions(item, actions)
            {
                var $item = $(item);
                var $commands = $item.find('.commands').first();

                $.each(actions, function (index, action)
                {
                    var $command = create_command(action);
                    $command.click(function(e)
                    {
                        $['on_' + action](e);
                    });
                    $commands.append($command);
                }, this);
            }

            var activity_actions = ['movedir', 'move', 'delete'];
            if(course)
            {
                activity_actions.push('restore');
            }

            var directory_actions = ['delete', 'restore'];

            // Initialize items
            $block.find('li.activity').each(function(index, item)
            {
                add_actions(item, activity_actions);
            });

            // Initialize directory items
            $block.find('li.directory').each(function(index, item)
            {
                add_actions(item, directory_actions);
            });

            // Initialize directories
            directories.init();
        };

        $.init_activity_commands = function()
        {
            function add_backup_comand($activity)
            {
                var $menu = $activity.find("ul[role='menu']");

                if($menu.length)
                {
                    var li = $menu.find('li').first().clone();
                    var $backup = li.find('a').attr('title', str('backup')).attr('href', 'javascript:void(0)');
                    var img = li.find('img');

                    if (img.length) {
                        li.find('img').attr('alt', str('backup')).attr('title', str('backup')).attr('src', M.util.image_url(icon['backup'].pix));
                    } else {
                        li.find('i').attr('class', 'icon fa fa-upload').attr('title', str('backup')).attr('aria-label', str('backup'));
                    }

                    li.find('span').html(str('backup'));
                    $menu.append(li);
                    // if($menu.find('i.fa').length)
                    // {
                    //     $backup.find("img").replaceWith($("<i class='fa fa-cloud-download icon'>"));
                    // }
                }
                else
                {
                    var $backup = create_command("backup");
                    $menu = $activity.find('div[role="menu"]');
                    if($menu.length)
                    {
                        $backup = create_special_activity_command("backup");
                        $menu.append($backup.attr("role", "menuitem"));
                        if($menu.css("display") === "none")
                        {
                            $backup.append($("<span class='menu-action-text'/>").append($backup.attr('title')));
                        }
                        // if($menu.find("i.fa"))
                        // {
                        //     $backup.find("img").replaceWith($("<i class='fa fa-cloud-download icon'/>"));
                        // }
                    }
                    else
                    {
                        $activity.find(".commands").append($backup);
                    }
                }

                $backup.click(function(e)
                {
                    $.on_backup(e);
                });
            }

            if(course.is_frontpage)
            {
                $(".sitetopic li.activity").each(function()
                {
                    add_backup_comand($(this));
                });
                $(".block_site_main_menu .content > ul > li").each(function()
                {
                    add_backup_comand($(this));
                });
            }
            else
            {
                $(".course-content li.activity").each(function()
                {
                    add_backup_comand($(this));
                });
            }

            $("li.section").each(function()
            {
                var sectionID = $(this).find("div.content h3.sectionname span.inplaceeditable").attr("data-itemid");

                var $menu = $(this).find("ul[role='menu']").first();

                if($menu.length)
                {
                    var li = $menu.find('li').first().clone();
                    var img = li.find('img');

                    if (img.length) {
                        img.attr('alt', str('backup')).attr('title', str('backup')).attr('src', M.util.image_url(icon['backup'].pix, null));
                    } else {
                        li.find('i').attr('class', 'icon fa fa-upload').attr('title', str('backup')).attr('aria-label', str('backup'));
                    }

                    li.find('span').html(str('backup'));
                    li.find('a').attr('href', 'javascript:void(0)');

                    $menu.append(li);

                    li.find('a').click(function()
                    {
                        $.on_section_backup(sectionID);
                    });
                }
                else
                {
                    $menu = $(this).find("div[role='menu']").first();

                    var $backup = null;

                    if($menu.length)
                    {
                        $backup = create_special_activity_command("backup");

                        $menu.append($backup.attr("role", "menuitem"));

                        if($menu.css("display") === "none")
                        {
                            $backup.append($("<span class='menu-action-text'/>").append($backup.attr('title')));
                        }
                        // if($menu.find("i.fa"))
                        // {
                            // $backup.find("img").replaceWith($("<i class='fa fa-cloud-download icon'/>"));
                        // }
                    }
                    else
                    {
                        $backup = create_command("backup");
                        $activity.find(".commands").append($backup);
                    }

                    $backup.click(function ()
                    {
                        $.on_section_backup(sectionID);
                    });
                }
            });
        };

        /**
         * Initialize the Sharing Cart block
         */
        $.init = function()
        {
            M.str.block_sharing_cart['pluginname'] = this.get_plugin_name();

            // arrange header icons (bulkdelete, help)
            $.init_block_header();
            $.init_item_tree();
            $.init_activity_commands();
        };

        var WAITICON = {'pix': 'i/loading', 'component': 'moodle'};
        var $spinner = $('<img/>').attr('src', M.util.image_url(WAITICON.pix, WAITICON.component)).addClass('spinner');
        $('div#sharing-cart-spinner-modal div.spinner-container').prepend($spinner);

        $.init();
    })
});