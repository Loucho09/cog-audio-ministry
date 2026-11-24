<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;

/**
 * Server-side rule for the homepage "published" flag on gallery images and team members.
 *
 * - Super-admin: the form value is used as is (the toggle defaults on for new items).
 * - Everyone else: any submitted value is thrown away. New items start unpublished.
 *   Editing a published item and changing what visitors would see (content fields)
 *   puts it back to unpublished until a super-admin approves it again. Changing only
 *   things like sort order leaves it published.
 */
class PublishGuard
{
    /**
     * @param  array<string, mixed>  $data
     * @param  list<string>  $contentFields
     * @return array<string, mixed>
     */
    public static function apply(array $data, ?Model $record, array $contentFields): array
    {
        if (auth()->user()?->hasRole('super-admin')) {
            return $data;
        }

        unset($data['is_published']);

        if ($record === null || ! $record->exists) {
            $data['is_published'] = false;

            return $data;
        }

        if ($record->is_published && self::contentChanged($data, $record, $contentFields)) {
            $data['is_published'] = false;
        }

        return $data;
    }

    /** @param  list<string>  $contentFields */
    private static function contentChanged(array $data, Model $record, array $contentFields): bool
    {
        foreach ($contentFields as $field) {
            if (array_key_exists($field, $data) && (string) ($data[$field] ?? '') !== (string) ($record->getAttribute($field) ?? '')) {
                return true;
            }
        }

        return false;
    }
}
