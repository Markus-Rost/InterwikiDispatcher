<?php
namespace MediaWiki\Extension\InterwikiDispatcher;

use Config;
use Html;
use MediaWiki\Interwiki\InterwikiLookup;
use SpecialPage;

class ExtensionInterwikiHooks implements \MediaWiki\SpecialPage\Hook\SpecialPageAfterExecuteHook {
    private array $rules;
    private InterwikiLookup $interwikiLookup;

    public function __construct(
        Config $config,
        InterwikiLookup $interwikiLookup
    ) {
        $this->rules = $config->get( 'IWDPrefixes' );
        $this->interwikiLookup = $interwikiLookup;
    }

    /**
     * Displays a table of IWD prefixes on the main page of Special:Interwiki.
     *
     * @param SpecialPage $special
     * @param string|null $subPage Subpage string, or null if no subpage was specified
     * @return bool|void True or no return value to continue or false to abort
     */
    public function onSpecialPageAfterExecute( $special, $subPage ) {
        if ( !$special instanceof \MediaWiki\Extension\Interwiki\SpecialInterwiki
            || in_array( $subPage, [ 'edit', 'add', 'delete' ] )
        ) {
            return;
        }

        $out = $special->getOutput();

        $out->addHTML(
            '<h2 id="interwikidispatchertable">' .
            $special->msg( 'interwikidispatcher-specialpage-heading' )->parse() .
            '</h2>'
        );
        $out->addWikiMsg( 'interwikidispatcher-specialpage-description' );
        $out->addHTML( $this->makeTable( $special ) );
        $out->addModuleStyles( [
            'ext.interwikidispatcher.specialinterwiki'
        ] );
    }

    private function makeTable( SpecialPage $special ): string {
        // Output the header
        $out = Html::openElement(
            'table',
            [ 'class' => 'mw-interwikitable wikitable sortable body' ]
        ) . "\n";
        $out .= Html::openElement( 'thead' ) .
            Html::openElement( 'tr', [ 'class' => 'interwikitable-header' ] ) .
            Html::element( 'th', [], $special->msg( 'interwiki_prefix' )->text() ) .
            Html::element( 'th', [], $special->msg( 'interwiki_url' )->text() ) .
            Html::element( 'th', [], $special->msg( 'interwikidispatcher-specialpage-inturl' )->text() ) .
            Html::element( 'th', [], $special->msg( 'interwikidispatcher-specialpage-checksexists' )->text() ) .
            Html::element( 'th', [], $special->msg( 'interwiki_trans' )->text() );
        $out .= Html::closeElement( 'tr' ) .
            Html::closeElement( 'thead' ) . "\n" .
            Html::openElement( 'tbody' );

        // Output configured prefix table rows
        foreach ( $this->rules as $rule ) {
            $vanillaInterwiki = $this->interwikiLookup->fetch( $rule['interwiki'] );

            // Build the prefix
            $prefix = $rule['interwiki'];
            if ( ( $rule['subprefix'] ?? null ) !== null ) {
                $prefix .= ':' . $rule['subprefix'];
            }

            $attribs = [ 'class' => 'mw-interwikitable-row' ];
            if ( !$vanillaInterwiki ) {
                $attribs['class'] .= ' ext-interwikidispatcher-missing';
            }
            $out .= Html::openElement( 'tr', $attribs );

            $out .= Html::element( 'td', [ 'class' => 'mw-interwikitable-prefix' ], $prefix );
            $out .= Html::element(
                'td',
                [ 'class' => 'mw-interwikitable-url' ],
                $rule['url']
            );
            $out .= Html::element(
                'td',
                [ 'class' => 'mw-interwikitable-url' ],
                $rule['urlInt'] ?? '-'
            );

            $hasExistenceCheck = ( $rule['wikiExistsCallback'] ?? $rule['dbname'] ?? null ) !== null;
            $attribs = [ 'class' => 'ext-interwikidispatcher-checksexists' ];
            if ( $hasExistenceCheck ) {
                $attribs['class'] .= ' ext-interwikidispatcher-checksexists-yes';
            }
            // The messages interwiki_0 and interwiki_1 are used here
            $contents = $special->msg( 'interwiki_' . ( $hasExistenceCheck ? '1' : '0' ) )->text();
            $out .= Html::element( 'td', $attribs, $contents );

            $canTransclude = !( $rule['baseTransOnly'] ?? false )
                && $vanillaInterwiki && $vanillaInterwiki->isTranscludable();
            $attribs = [ 'class' => 'mw-interwikitable-trans' ];
            if ( $canTransclude ) {
                $attribs['class'] .= ' mw-interwikitable-trans-yes';
            }
            // The messages interwiki_0 and interwiki_1 are used here.
            $contents = $special->msg( 'interwiki_' . ( $canTransclude ? '1' : '0' ) )->text();
            $out .= Html::element( 'td', $attribs, $contents );

            $out .= Html::closeElement( 'tr' ) . "\n";
        }
        $out .= Html::closeElement( 'tbody' ) .
            Html::closeElement( 'table' );

        return $out;
    }
}
