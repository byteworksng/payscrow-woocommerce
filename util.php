<?php
/**
 * Created by Byteworks Limited.
 * Author: Chibuzor Ogbu
 * Date: 5/30/16
 * Time: 2:16 AM
 */

/**
 * make truncated copies of string and output all html characters
 * @param string $item_name
 * @return string
 */
function truncate_item_name( $item_name)
{
    if (strlen($item_name) > 127) {
        $item_name = substr($item_name, 0, 124) . '...';
    }
    return html_entity_decode($item_name, ENT_NOQUOTES, 'UTF-8');
}

