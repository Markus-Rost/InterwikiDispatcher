<?php
namespace MediaWiki\Extension\InterwikiDispatcher;

use Config;
use Title;

class InterwikiDispatcherHooks implements \MediaWiki\Hook\GetLocalURLHook {
    private array $rules;

    public function __construct( Config $config ) {
        $this->rules = $config->get( 'IWDPrefixes' );
    }

    /**
     * @param Title $title Title object of page
     * @param string &$url String value as output (out parameter, can modify)
     * @param string $query Query options as string passed to Title::getLocalURL()
     * @return bool|void True or no return value to continue or false to abort
     */
    public function onGetLocalURL( $title, &$url, $query ) {
        foreach ( $this->rules as $key => $rule ) {
            if ( $this->onGetLocalURLSingle( $title, $url, $query, $rule ) === false ) {
                return false;
            }
        }
        return true;
    }

    public function onGetLocalURLSingle( $title, &$url, $query, $rule ) {
        if ( $title->getInterwiki() !== $rule['interwiki'] ) {
            return true;
        }
        $namespace = $title->getNsText();
        if ( $namespace != '' ) {
            # Can this actually happen? Interwikis shouldn't be parsed.
            # Yes! It can in interwiki transclusion. But... it probably shouldn't.
            $namespace .= ':';
        }
        $dbkey = $namespace . $title->getDBKey();
        $subprefix = $rule['subprefix'] ?? '';
        if ( $subprefix !== '' ) $subprefix .= '_*:_*';
        $m = [];
        if ( $dbkey !== '' && preg_match( "/^$subprefix(?:([a-z-]{2,12})\.)?([a-z\d-]{1,50})(?:_*:_*(.*))?$/Si", $dbkey, $m ) ) {
            if ( !isset( $m[3] ) ) $m[3] = '';
            [ , $language, $wiki, $article ] = $m;
            if ( $language === '' ) {
                # $articlePath = 'https://$2.wiki.gg/wiki/$1'
                $articlePath = $rule['url'];
            }
            else {
                # $articlePath = 'https://$2.wiki.gg/$3/wiki/$1'
                $articlePath = $rule['urlInt'];
                if ( !isset( $articlePath ) ) {
                    return true;
                }
                $articlePath = str_replace( "$3", strtolower( $language ), $articlePath );
            }
            $articlePath = str_replace( "$2", strtolower( $wiki ), $articlePath );
            $url = str_replace( "$1", wfUrlencode( $article ?? '' ), $articlePath );
            $url = wfAppendQuery( $url, $query );
            return false;
        }
        return true;
    }
}
