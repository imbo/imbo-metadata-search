<?php

namespace Imbo\MetadataSearch\EventListener;

use Imbo\EventListener\AccessToken as ImboAccessToken;

/**
 * Access token event listener adding event hook until introduced in
 * Imbo core. The event makes it possible for custom resources to hook
 * onto the core auth flow.
 *
 * @author Kristoffer Brabrand <kristoffer@brabrand.net>
 */
class AccessToken extends ImboAccessToken {
    public static function getSubscribedEvents() {
        return [
            'auth.accesstoken' => ['checkAccessToken' => 100],
        ];
    }
}