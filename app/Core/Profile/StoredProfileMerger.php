<?php

namespace App\Core\Profile;

final class StoredProfileMerger
{
    /**
     * @param  array<string, mixed>  $entity
     * @param  array<string, mixed>  $stored
     * @param  array<int, string>  $fields
     * @return array<string, mixed>
     */
    public static function apply(array $entity, array $stored, array $fields): array
    {
        if ($stored === []) {
            return $entity;
        }

        foreach ($fields as $field) {
            if (! array_key_exists($field, $stored)) {
                continue;
            }

            $value = $stored[$field];

            if ($value === null || $value === '') {
                continue;
            }

            $entity[$field] = $value;
        }

        return $entity;
    }
}
