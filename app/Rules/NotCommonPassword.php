<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Cache;

/**
 * Rejects passwords that appear in the bundled list of common/leaked passwords.
 *
 * The list lives in resources/passwords/common-passwords.txt and is loaded from
 * disk once, then cached forever (invalidated by the file's checksum).
 */
class NotCommonPassword implements Rule
{
    /**
     * The validation message shown when the password is too common.
     *
     * @var string
     */
    public const MESSAGE = 'This password is just too common. Please try another!';

    /**
     * The cache key used to store the parsed password list.
     *
     * @var string
     */
    protected $cacheKey = 'common_password_list';

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if (!is_string($value)) {
            return true;
        }

        $list = $this->passwordList();

        if ($list === null) {
            return true;
        }

        return !$list->contains(strtolower($value));
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return self::MESSAGE;
    }

    /**
     * Get the lower-cased list of common passwords, cached forever.
     *
     * The cache key includes the md5 checksum of the list file so that the
     * cached copy is invalidated automatically whenever the file changes.
     *
     * @return \Illuminate\Support\Collection|null
     */
    protected function passwordList()
    {
        $path = resource_path('passwords/common-passwords.txt');

        if (!is_file($path)) {
            return null;
        }

        $cacheKey = $this->cacheKey.'_'.md5_file($path);

        return Cache::rememberForever($cacheKey, function () use ($path) {
            return collect(explode("\n", file_get_contents($path)))
                ->map(function ($password) {
                    return strtolower($password);
                });
        });
    }
}
