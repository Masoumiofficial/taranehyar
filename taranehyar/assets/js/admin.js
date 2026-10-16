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

    function fieldValue( form, name ) {
        var field = form && form.elements ? form.elements.namedItem( name ) : null;
        if ( ! field ) return '';
        if ( field.type === 'file' ) return field.files && field.files.length ? field.files[0].name : '';
        return typeof field.value === 'string' ? field.value.trim() : '';
    }

    function persianDigits( value ) {
        var digits = '۰۱۲۳۴۵۶۷۸۹';
        return String( value ).replace( /[0-9]/g, function ( digit ) { return digits.charAt( Number( digit ) ); } );
    }

    function copyVariables( form, type ) {
        var album = type === 'album';
        var artist = fieldValue( form, album ? 'artist' : 'name' );
        var track = fieldValue( form, album ? 'song' : 'track' );
        var artistEn = fieldValue( form, album ? 'artist_en' : 'enname' );
        var trackEn = fieldValue( form, album ? 'song_en' : 'entrack' );
        var trackCount = 0;
        if ( album && form.querySelectorAll ) {
            form.querySelectorAll( 'input[name^="track_name_"]' ).forEach( function ( input ) {
                if ( input.value.trim() ) trackCount++;
            } );
        }
        return {
            artist: artist,
            track: track,
            artist_en: artistEn,
            track_en: trackEn,
            album: album ? track : '',
            album_en: album ? trackEn : '',
            track_count: album ? persianDigits( trackCount ) : '',
            track_count_en: album ? String( trackCount ) : '',
            // کیفیت در کارت مشخصات نمایش داده می‌شود تا در متن معرفی تکرار نشود.
            quality_sentence: '',
            quality_sentence_en: ''
        };
    }

    function updateCopyPreview( select ) {
        var form = select.closest ? select.closest( 'form' ) : null;
        var preview = document.getElementById( select.getAttribute( 'data-preview-id' ) || '' );
        var option = select.options[ select.selectedIndex ];
        if ( ! preview || ! option ) return;

        var template = option.getAttribute( 'data-template' ) || '';
        if ( template === '' ) {
            preview.textContent = select.getAttribute( 'data-language' ) === 'en' ? 'No introduction will be added.' : 'برای این نوشته مقدمه‌ای درج نمی‌شود.';
            return;
        }
        var vars = copyVariables( form, select.getAttribute( 'data-content-type' ) || 'music' );
        preview.textContent = template.replace( /\{([^}]+)\}/g, function ( token, key ) {
            return Object.prototype.hasOwnProperty.call( vars, key ) ? vars[ key ] : '';
        } ).replace( /\s+\./g, '.' ).trim();
    }

    document.addEventListener( 'DOMContentLoaded', function () {
        var theme = getSavedTheme();
        applyTheme( theme );

        var toggle = document.getElementById( 'ty-theme-toggle' );
        if ( toggle ) {
            toggle.addEventListener( 'click', function () {
                var currentWrap = document.querySelector( '.ty-wrap' );
                var current = currentWrap ? currentWrap.getAttribute( 'data-theme' ) || 'light' : 'light';
                var next = current === 'dark' ? 'light' : 'dark';
                applyTheme( next );
                saveTheme( next );
            } );
        }

        document.querySelectorAll( '[data-ty-copy-template]' ).forEach( function ( select ) {
            var form = select.closest ? select.closest( 'form' ) : null;
            updateCopyPreview( select );
            select.addEventListener( 'change', function () { updateCopyPreview( select ); } );
            if ( form ) {
                form.addEventListener( 'input', function () {
                    form.querySelectorAll( '[data-ty-copy-template]' ).forEach( updateCopyPreview );
                } );
                form.addEventListener( 'change', function () {
                    form.querySelectorAll( '[data-ty-copy-template]' ).forEach( updateCopyPreview );
                } );
            }
        } );

        document.querySelectorAll( '[data-ty-settings-expand]' ).forEach( function ( button ) {
            button.addEventListener( 'click', function () {
                document.querySelectorAll( '.ty-settings-form details' ).forEach( function ( group ) { group.open = true; } );
            } );
        } );
        document.querySelectorAll( '[data-ty-settings-collapse]' ).forEach( function ( button ) {
            button.addEventListener( 'click', function () {
                document.querySelectorAll( '.ty-settings-form details' ).forEach( function ( group ) { group.open = false; } );
            } );
        } );

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
