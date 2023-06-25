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
        global $wgLocalDatabases;
        if ( $title->getInterwiki() !== $rule['interwiki'] ) {
            return true;
        }
        if ( ( $rule['baseTransOnly'] ?? false ) === true && preg_match( "/(?:^|&)action=(?:render|raw)(?:&|$)/Si", $query ) ) {
            return true;
        }
        $dbkey = $title->getDBKey();
        $subprefix = $rule['subprefix'] ?? '';
        if ( $subprefix !== '' ) $subprefix .= '_*:_*';
        $m = [];
        if ( $dbkey !== '' && preg_match( "/^$subprefix(?:([a-z-]{2,12})\.)?([a-z\d-]{1,50})(?:_*:_*(.*))?$/Si", $dbkey, $m ) ) {
            if ( !isset( $m[3] ) ) $m[3] = '';
            [ , $language, $wiki, $article ] = $m;
            $wiki = strtolower( $wiki );
            if ( $language === '' ) {
                # $articlePath = 'https://$2.wiki.gg/wiki/$1'
                $articlePath = $rule['url'];
                $dbname = $rule['dbname'] ?? null;
            }
            else {
                $language = strtolower( $language );
                # $articlePath = 'https://$2.wiki.gg/$3/wiki/$1'
                $articlePath = $rule['urlInt'] ?? null;
                if ( $articlePath === null ) {
                    return true;
                }
                $articlePath = str_replace( '$3', $language, $articlePath );
                $dbname = $rule['dbnameInt'] ?? null;
                if ( $dbname !== null ) {
                    $dbname = str_replace( '$3', $language, $dbname );
                }
            }
            if ( $dbname !== null ) {
                $dbname = str_replace( '$2', $wiki, $dbname );
                if ( !in_array( $dbname, $wgLocalDatabases ) ) {
                    return true;
                }
            }
            $articlePath = str_replace( '$2', $wiki, $articlePath );
            $namespace = $title->getNsText();
            if ( $namespace != '' ) {
                # Can this actually happen? Interwikis shouldn't be parsed.
                # Yes! It can in interwiki transclusion. But... it probably shouldn't.
                $namespace .= ':';
            }
            $article = $namespace . ( $article ?? '' );
            $url = str_replace( '$1', wfUrlencode( $article ), $articlePath );
            $url = wfAppendQuery( $url, $query );
            return false;
        }
        return true;
    }
}
