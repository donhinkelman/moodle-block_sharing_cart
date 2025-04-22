<?php

namespace block_sharing_cart\app\item;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();

// @codeCoverageIgnoreEnd

class entity extends \block_sharing_cart\app\entity
{
    public const STATUS_AWAITING_BACKUP = 0;
    public const STATUS_BACKEDUP = 1;
    public const STATUS_BACKUP_FAILED = 2;

    public const TYPE_SECTION = 'section';

    Public const CURRENT_BACKUP_VERSION = 3;

    public function get_user_id(): int
    {
        return $this->record['user_id'] ?? 0;
    }

    public function set_user_id(int $value): self
    {
        $this->record['user_id'] = $value;
        return $this;
    }

    public function get_file_id(): ?int
    {
        return $this->record['file_id'] ?? null;
    }

    public function set_file_id(?int $value): self
    {
        $this->record['file_id'] = $value;
        return $this;
    }

    public function get_parent_item_id(): ?int
    {
        return $this->record['parent_item_id'] ?? null;
    }

    public function set_parent_item_id(?int $value): self
    {
        $this->record['parent_item_id'] = $value;
        return $this;
    }

    public function get_old_instance_id(): ?int
    {
        return $this->record['old_instance_id'] ?? null;
    }

    public function set_old_instance_id(?int $value): self
    {
        $this->record['old_instance_id'] = $value;
        return $this;
    }

    public function get_type(): string
    {
        return $this->record['type'] ?? self::TYPE_SECTION;
    }

    public function set_type(string $value): self
    {
        $this->record['type'] = $value;
        return $this;
    }

    public function get_name(): string
    {
        return $this->record['name'] ?? '';
    }

    public function set_name(string $value): self
    {
        $this->record['name'] = $value;
        return $this;
    }

    public function get_status(): int
    {
        return $this->record['status'] ?? self::STATUS_AWAITING_BACKUP;
    }

    public function set_status(int $value): self
    {
        $this->record['status'] = $value;
        return $this;
    }

    public function get_sortorder(): ?int
    {
        return $this->record['sortorder'] ?? null;
    }

    public function set_sortorder(?int $value): self
    {
        $this->record['sortorder'] = $value;
        return $this;
    }

    public function get_version(): int
    {
        return $this->record['version'] ?? self::CURRENT_BACKUP_VERSION;
    }

    public function get_timecreated(): int
    {
        return $this->record['timecreated'] ?? 0;
    }

    public function set_timecreated(int $value): self
    {
        $this->record['timecreated'] = $value;
        return $this;
    }

    public function get_timemodified(): int
    {
        return $this->record['timemodified'] ?? 0;
    }

    public function set_timemodified(int $value): self
    {
        $this->record['timemodified'] = $value;
        return $this;
    }

    public function is_section(): bool
    {
        return $this->get_type() === self::TYPE_SECTION;
    }

    public function is_module(): bool
    {
        return !$this->is_section();
    }

    public function get_original_course_fullname(): ?string
    {
        return $this->record['original_course_fullname'] ?? null;
    }

    public function set_original_course_fullname(?string $value): self
    {
        $this->record['original_course_fullname'] = $value;
        return $this;
    }

    public function to_array(): array
    {
        return [
            'id' => $this->get_id(),
            'user_id' => $this->get_user_id(),
            'file_id' => $this->get_file_id(),
            'parent_item_id' => $this->get_parent_item_id(),
            'old_instance_id' => $this->get_old_instance_id(),
            'type' => $this->get_type(),
            'name' => format_string($this->get_name()),
            'status' => $this->get_status(),
            'sortorder' => $this->get_sortorder(),
            'original_course_fullname' => $this->get_original_course_fullname(),
            'version' => $this->get_version(),
            'timecreated' => $this->get_timecreated(),
            'timemodified' => $this->get_timemodified(),
        ];
    }
}
