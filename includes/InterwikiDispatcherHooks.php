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
        if ( $title->getInterwiki() !== 'gg' ) {
            return true;
        }
        $namespace = $title->getNsText();
        if ( $namespace != '' ) {
            # Can this actually happen? Interwikis shouldn't be parsed.
            # Yes! It can in interwiki transclusion. But... it probably shouldn't.
            $namespace .= ':';
        }
        $dbkey = $namespace . $title->getDBKey();
        $m = [];

        if ( !empty( $dbkey ) && preg_match( "/^c_*:_*(?:([a-z-]{2,12})\.)?([a-z\d-]{1,50})(?:_*:_*(.*))?$/Si", $dbkey, $m ) ) {
            if ( !isset( $m[3] ) ) $m[3] = '';
            [ , $language, $wiki, $article ] = $m;
            $wiki = strtolower( $wiki );
            if ( empty( $language ) ) {
                $articlePath = "https://$wiki.wiki.gg/wiki/$1";
            } else {
                $language = strtolower( $language );
                $articlePath = "https://$wiki.wiki.gg/$language/wiki/$1";
            }
            $url = str_replace( "$1", wfUrlencode( $article ?? '' ), $articlePath );
            $url = wfAppendQuery( $url, $query );
            return false;
        }
        return true;
    }
}
