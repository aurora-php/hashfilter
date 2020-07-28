<?php

declare(strict_types=1);

/*
 * This file is part of the 'octris/hashfilter' package.
 *
 * (c) Harald Lapp <harald@octris.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Octris\Streamfilter;

/**
 * Filter for incremental hashing.
 *
 * @copyright   copyright (c) 2020-present by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
class Hashfilter extends \php_user_filter
{
    /**
     * Hash context.
     *
     * @var     HashContext
     */
    protected $context;

    /**
     * Called when creating the filter.
     *
     * @return  bool
     */
    public function onCreate(): bool
    {
        if (!($this->params instanceof \stdClass) || !isset($this->params->algo)) {
            return false;
        }

        $this->context = hash_init($this->params->algo);

        return true;
    }

    /**
     * Called when closing the filter.
     */
    public function onClose(): void
    {
        $this->params->hash = hash_final($this->context);
    }

    /**
     * Called when filter is applied.
     *
     * @param   resource    $in
     * @param   resource    $out
     * @param   int         &$consumed
     * @param   bool        $closing
     * @return  int
     */
    public function filter($in, $out, &$consumed, $closing): int
    {
        while ($bucket = stream_bucket_make_writeable($in)) {
            $consumed += $bucket->datalen;

            hash_update($this->context, $bucket->data);

            stream_bucket_append($out, $bucket);
        }

        return PSFS_PASS_ON;
    }

    /**
     * Helper method to register stream filter.
     *
     * @return  bool
     */
    public static function registerFilter(): bool
    {
        return stream_filter_register(static::class, static::class);
    }

    /**
     * Helper method to append filter to stream.
     *
     * @param   resource    $stream         Stream to append filter to.
     * @param   \stdClass   $params         Parameter(s) for Hashfilter, the 'algo' property must be set to an appropriate algorithm.
     * @param   int         $read_write     Filter chain to append to.
     * @return  resource|bool               Resource of filter (for usage with eg.: stream_filter_remove) or false on failure.
     */
    public static function appendFilter($stream, \stdClass $params, int $read_write = STREAM_FILTER_WRITE)
    {
        self::registerFilter();

        return stream_filter_append($stream, static::class, $read_write, $params);
    }
}
