(function () {
    'use strict';

    var STORAGE_KEY = 'ty_theme_v2';
    var root = document.documentElement;

    function applyTheme( theme ) {
        document.querySelectorAll( '.ty-wrap' ).forEach( function ( el ) {
            el.setAttribute( 'data-theme', theme );
        } );
    }

    function getSavedTheme() {
        try {
            return localStorage.getItem( STORAGE_KEY ) || 'light';
        } catch ( e ) {
            return 'light';
        }
    }

    function saveTheme( theme ) {
        try {
            localStorage.setItem( STORAGE_KEY, theme );
        } catch ( e ) {}
    }

    document.addEventListener( 'DOMContentLoaded', function () {
        var theme = getSavedTheme();
        applyTheme( theme );

        var toggle = document.getElementById( 'ty-theme-toggle' );
        if ( toggle ) {
            toggle.addEventListener( 'click', function () {
                var current = document.querySelector( '.ty-wrap' ).getAttribute( 'data-theme' ) || 'dark';
                var next = current === 'dark' ? 'light' : 'dark';
                applyTheme( next );
                saveTheme( next );
            } );
        }

        // افکت ریپل ساده روی دکمه ارسال
        document.querySelectorAll( '.ty-btn-primary' ).forEach( function ( btn ) {
            btn.addEventListener( 'click', function ( e ) {
                var ripple = document.createElement( 'span' );
                ripple.className = 'ty-ripple';
                var rect = btn.getBoundingClientRect();
                ripple.style.left = ( e.clientX - rect.left ) + 'px';
                ripple.style.top = ( e.clientY - rect.top ) + 'px';
                btn.appendChild( ripple );
                setTimeout( function () { ripple.remove(); }, 600 );
            } );
        } );

        document.querySelectorAll( 'form[data-confirm]' ).forEach( function ( form ) {
            form.addEventListener( 'submit', function ( event ) {
                if ( ! window.confirm( form.getAttribute( 'data-confirm' ) || 'این عملیات انجام شود؟' ) ) {
                    event.preventDefault();
                }
            } );
        } );

        document.querySelectorAll( '.ty-settings-shortcuts a' ).forEach( function ( link ) {
            link.addEventListener( 'click', function () {
                var target = document.querySelector( link.getAttribute( 'href' ) );
                if ( target && target.tagName.toLowerCase() === 'details' ) target.open = true;
            } );
        } );

        // پیش‌نمایش زنده‌ی فایل آپلودی روی دکمه آپلود
        document.querySelectorAll( '.ty-file-upload input[type=file]' ).forEach( function ( input ) {
            input.addEventListener( 'change', function () {
                var textEl = input.parentElement.querySelector( '.upload-text' );
                if ( textEl && input.files && input.files[0] ) {
                    textEl.textContent = '✓ ' + input.files[0].name;
                    input.parentElement.classList.add( 'has-file' );
                }
            } );
        } );
    } );
})();
