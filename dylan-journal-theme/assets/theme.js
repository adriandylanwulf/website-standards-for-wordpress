( function () {
  'use strict';

  const menu = document.querySelector( '.dj-menu' );
  const menuBreakpoint = window.matchMedia( '(max-width: 760px)' );

  if ( menu ) {
    const trigger = menu.querySelector( '.dj-menu__trigger' );

    const syncTriggerState = function () {
      if ( trigger ) {
        trigger.setAttribute( 'aria-expanded', menu.hasAttribute( 'open' ) ? 'true' : 'false' );
      }
    };

    const syncMenuForViewport = function () {
      if ( menuBreakpoint.matches ) {
        menu.removeAttribute( 'open' );
      } else {
        menu.setAttribute( 'open', '' );
      }

      syncTriggerState();
    };

    syncMenuForViewport();

    // Keep the native details element as the no-JavaScript fallback, but
    // control the mobile interaction explicitly. This avoids a stuck menu
    // on browsers that do not reliably toggle summary after the menu opens.
    if ( trigger ) {
      trigger.addEventListener( 'click', function ( event ) {
        if ( ! menuBreakpoint.matches ) {
          return;
        }

        event.preventDefault();

        if ( menu.hasAttribute( 'open' ) ) {
          menu.removeAttribute( 'open' );
        } else {
          menu.setAttribute( 'open', '' );
        }

        syncTriggerState();
      } );
    }

    menu.addEventListener( 'toggle', syncTriggerState );

    document.addEventListener( 'click', function ( event ) {
      if ( menuBreakpoint.matches && menu.hasAttribute( 'open' ) && ! menu.contains( event.target ) ) {
        menu.removeAttribute( 'open' );
        syncTriggerState();
      }
    } );

    menu.addEventListener( 'click', function ( event ) {
      const link = event.target.closest( 'a' );

      if ( link && menuBreakpoint.matches ) {
        menu.removeAttribute( 'open' );
        syncTriggerState();
      }
    } );

    menu.addEventListener( 'keydown', function ( event ) {
      if ( 'Escape' === event.key && menuBreakpoint.matches && menu.hasAttribute( 'open' ) ) {
        event.preventDefault();
        menu.removeAttribute( 'open' );
        syncTriggerState();
        trigger.focus();
      }
    } );

    if ( typeof menuBreakpoint.addEventListener === 'function' ) {
      menuBreakpoint.addEventListener( 'change', syncMenuForViewport );
    } else if ( typeof menuBreakpoint.addListener === 'function' ) {
      menuBreakpoint.addListener( syncMenuForViewport );
    }
  }

  const getCookieCmp = function () {
    if ( window.Cookiebot && typeof window.Cookiebot.renew === 'function' ) {
      return {
        open: function () {
          return window.Cookiebot.renew();
        }
      };
    }

    if ( window.__ucCmp && typeof window.__ucCmp.showSecondLayer === 'function' ) {
      return {
        open: function () {
          return window.__ucCmp.showSecondLayer();
        }
      };
    }

    if ( window.UC_UI && typeof window.UC_UI.showSecondLayer === 'function' ) {
      return {
        open: function () {
          return window.UC_UI.showSecondLayer();
        }
      };
    }

    return null;
  };

  const openCookieSettings = function ( button ) {
    const status = document.getElementById( 'cookie-settings-status' );
    const setStatus = function ( message ) {
      if ( status ) {
        status.textContent = message;
      }
    };

    const existingCmp = getCookieCmp();

    if ( existingCmp ) {
      Promise.resolve( existingCmp.open() ).catch( function () {} );
      setStatus( '' );
      return;
    }

    const settingsId = button.getAttribute( 'data-cookie-settings-id' );

    if ( ! settingsId ) {
      setStatus( 'Die Cookie-Einstellungen sind noch nicht konfiguriert.' );
      return;
    }

    if ( ! document.getElementById( 'Cookiebot' ) ) {
      const loader = document.createElement( 'script' );
      loader.id = 'Cookiebot';
      loader.async = true;
      loader.setAttribute( 'data-cbid', settingsId );
      loader.setAttribute( 'data-blockingmode', 'auto' );
      loader.setAttribute( 'data-widget-enabled', 'false' );
      loader.src = 'https://consent.cookiebot.com/uc.js';
      document.head.appendChild( loader );
    }

    setStatus( 'Die Cookie-Einstellungen werden geladen …' );

    const deadline = Date.now() + 7000;
    const waitForCmp = function () {
      const cmp = getCookieCmp();

      if ( cmp ) {
        setStatus( '' );
        Promise.resolve( cmp.open() ).catch( function () {
          setStatus( 'Die Cookie-Einstellungen konnten nicht geladen werden. Bitte versuche es später erneut.' );
        } );
        return;
      }

      if ( Date.now() < deadline ) {
        window.setTimeout( waitForCmp, 100 );
        return;
      }

      setStatus( 'Die Cookie-Einstellungen konnten nicht geladen werden. Bitte versuche es später erneut.' );
    };

    waitForCmp();
  };

  document.addEventListener( 'click', function ( event ) {
    const button = event.target.closest( '[data-cookie-settings]' );

    if ( button ) {
      openCookieSettings( button );
    }
  } );
}() );
