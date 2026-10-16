<?php
/**
 * Archive path helpers shared by all content types.
 *
 * New uploads are grouped by the first character of the English artist slug,
 * for example: music/a/alireza-rozegar, remix/a/artist-name, or nohe/a/artist-name.
 */
class TaranehYar_Archive {

    /** The only archive roots accepted by the plugin and the HostDL service. */
    public static function roots() {
        return array( 'music', 'remix', 'nohe', 'music-video', 'album', 'leech' );
    }

    /**
     * Make a lowercase ASCII slug from artist_en.
     *
     * @param string $artist_en English artist name.
     * @return string A safe slug, or "artist" when no ASCII letters/digits remain.
     */
    public static function artist_slug( $artist_en ) {
        $artist_en = (string) $artist_en;
        if ( function_exists( 'remove_accents' ) ) {
            $artist_en = remove_accents( $artist_en );
        }

        $slug = strtolower( $artist_en );
        $slug = preg_replace( '/[^a-z0-9]+/', '-', $slug );
        $slug = trim( (string) $slug, '-' );
        $slug = substr( $slug, 0, 120 );
        $slug = rtrim( $slug, '-' );

        return $slug !== '' ? $slug : 'artist';
    }

    /**
     * Build a date-free, sharded archive path for an artist.
     *
     * @param string $root One of music, remix, nohe, music-video, album, or leech.
     * @param string $artist_en English artist name.
     * @return string|false Relative archive path, or false for an unsupported root.
     */
    public static function path( $root, $artist_en ) {
        $root = (string) $root;
        if ( ! in_array( $root, self::roots(), true ) ) {
            return false;
        }

        $slug = self::artist_slug( $artist_en );
        return $root . '/' . substr( $slug, 0, 1 ) . '/' . $slug;
    }
}
