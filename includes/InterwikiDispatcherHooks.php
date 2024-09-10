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
     * Generates external links for titles in configured interwikis.
     *
     * @param Title $title Title object of page
     * @param string &$url String value as output (out parameter, can modify)
     * @param string $query Query options as string passed to Title::getLocalURL()
     * @return bool|void True or no return value to continue or false to abort
     */
    public function onGetLocalURL( $title, &$url, $query ) {
        foreach ( $this->rules as $rule ) {
            if ( $this->getLocalURLSingle( $rule, $title, $url, $query ) === false ) {
                return false;
            }
        }
        return true;
    }

    /**
     * Attempts to match a title against an external wikifarm interwiki rule, and generates a URL when successful.
     *
     * @param array $rule Farm interwiki settings.
     * @param Title $title Title object of page
     * @param string &$url String value as output (out parameter)
     * @param string $query Query options as string passed
     * @return bool True when $url not modified, false otherwise
     */
    private function getLocalURLSingle( $rule, $title, &$url, $query ) {
        // Check if the title matches the interwiki prefix
        if ( $title->getInterwiki() !== $rule['interwiki'] ) {
            return true;
        }

        // If only classic interwikis are allowed during transclusion, check if the query string contains render or raw
        // actions (used in interwiki transclusion) and stop parsing.
        if ( ( $rule['baseTransOnly'] ?? false ) === true &&
            preg_match( "/(?:^|&)action=(?:render|raw)(?:&|$)/Si", $query ?? '' )
        ) {
            return true;
        }

        // Suffix ':' (and ignore surrounding white-space) to the subprefix
        $subprefix = $rule['subprefix'] ?? '';
        if ( $subprefix !== '' ) {
            $subprefix .= '_*:_*';
        }

        // Parse the title, check if the wiki exists if possible, and form the final URL.
        //
        // To parse we use a regex, which roughly expects:
        // - configured subprefix
        // - [1]: optionally a language prefix (between 2 and 12 characters; a-z and hyphens allowed);
        // - [2]: wiki subdomain (between 1 and 50 characters; a-z, digits and hyphens allowed);
        // - optionally a colon (which may be wrapped with white-space, per standard link behaviour)...
        // - [3]: ... and the page title if a colon was found.
        $dbkey = $title->getDBKey();
        $m = [];
        if ( $dbkey !== '' &&
            preg_match( "/^$subprefix(?:([a-z-]{2,12})\.)?([a-z\d-]{1,50})(?:_*:_*(.*))?$/Si", $dbkey, $m )
        ) {
            if ( !isset( $m[3] ) ) {
                $m[3] = '';
            }
            [ , $language, $wiki, $article ] = $m;
            $wiki = strtolower( $wiki );
            $language = strtolower( $language );

            // Check if the wiki exists using either a provided callback or our default implementation
            $wikiExistsCallback = $rule['wikiExistsCallback'] ?? [ $this, 'doesWikiExist' ];
            if ( $wikiExistsCallback( $rule, $wiki, $language ) !== true ) {
                return true;
            }

            // Article path. This contains placeholders:
            // - $1: page title
            // - $2: wiki subdomain
            // - $3 (international links only): language code, can be an empty string
            if ( $language === '' ) {
                $articlePath = $rule['url'];
            } else {
                $articlePath = $rule['urlInt'] ?? null;
                if ( $articlePath === null ) {
                    return true;
                }
                $articlePath = str_replace( '$3', $language, $articlePath );
            }
            $articlePath = str_replace( '$2', $wiki, $articlePath );

            // Construct the final URL
            $namespace = $title->getNsText();
            if ( $namespace != '' ) {
                // Namespace is only set during interwiki transclusion. This is most likely "Template".
                $namespace .= ':';
            }
            $article = $namespace . ( $article ?? '' );
            $url = str_replace( '$1', wfUrlencode( $article ), $articlePath );
            $url = wfAppendQuery( $url, $query );

            return false;
        }

        return true;
    }

    /**
     * If dbname and/or dbnameInt are provided, checks if the remote wiki's DB name is in $wgLocalDatabases.
     *
     * @param array $rule
     * @param string &$wiki
     * @param string &$language
     * @return bool True to continue or false to abort
     */
    private function doesWikiExist( $rule, &$wiki, &$language ) {
        global $wgLocalDatabases;

        // This contains placeholders which are numbered identically to article paths:
        // - $2: wiki subdomain
        // - $3: language code, can be an empty string
        if ( $language === '' ) {
            $dbname = $rule['dbname'] ?? null;
        } else {
            $dbname = $rule['dbnameInt'] ?? null;
        }
        if ( $dbname !== null ) {
            $dbname = str_replace( '$2', $wiki, $dbname );
            $dbname = str_replace( '$3', $language, $dbname );
            return in_array( $dbname, $wgLocalDatabases );
        }

        return true;
    }
}
