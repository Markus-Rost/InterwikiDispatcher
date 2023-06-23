<?php
namespace MediaWiki\Extension\InterwikiDispatcher;

use Title;

class InterwikiDispatcher implements \MediaWiki\Hook\GetLocalURLHook {
    /**
     * @param Title $title Title object of page
     * @param string &$url String value as output (out parameter, can modify)
     * @param string $query Query options as string passed to Title::getLocalURL()
     * @return bool|void True or no return value to continue or false to abort
     */
    public function onGetLocalURL( $title, &$url, $query ) {
        if ( $title->getInterwiki() != 'gg' ) {
            return;
        }
        $namespace = $title->getNsText();
        if ( $namespace != '' ) {
            # Can this actually happen? Interwikis shouldn't be parsed.
            # Yes! It can in interwiki transclusion. But... it probably shouldn't.
            $namespace .= ':';
        }
        $dbkey = $namespace . $title->getDBKey();
        if ( $dbkey == '' ) {
            $url = 'https://support.wiki.gg/wiki/';
            return;
        }
        $m = [];
        if ( preg_match( "/^((?:[a-z-]{2,12}\\.)?[a-z\\d-]{1,50})(?:_*:_*(.*))?$/Si", $dbkey, $m ) ) {
            $wiki = explode( '.', strtolower( $m[1] ) );
            if ( count( $wiki ) > 2 ) {
                return false;
            }
            if ( count( $wiki ) == 2 ) {
                $wiki = "https://$wiki[1].wiki.gg/$wiki[0]/$1";
            }
            else {
                $wiki = "https://$wiki[0].wiki.gg/$1";
            }
            if ( !isset( $m[2] ) ) {
                $m[2] = '';
            }
            $url = str_replace( "$1", wfUrlencode( $m[2] ), $wiki );
            $url = wfAppendQuery( $url, $query );
        }
        else {
            return false;
        }
        return;
    }
}