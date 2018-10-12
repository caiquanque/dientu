<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'dientu');

/** MySQL database username */
define('DB_USER', 'admin');

/** MySQL database password */
define('DB_PASSWORD', 'admin');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8mb4');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'j]s3Gs.u]+L#tB/O+<-gdNnfJ)Q{#u1zFUp7Zo>iC;hY8h 1o^T;xR({(j]D,.OG');
define('SECURE_AUTH_KEY',  '~j](b70>?p7GT-Jf)q*S71TiMW9^A~ZuYo`aWZT#3a<l7M[J2SF9C1GG/ ~8f+Y`');
define('LOGGED_IN_KEY',    'IkQQ>.Y)DP$./=L/2.X^%p[226v(4VKHWNW.;YsGTli5c<eBwmw-[or/Sot#HfXv');
define('NONCE_KEY',        '!rs?Jx|Piw;>6R7@VOoW89n,K+KG ] L0VWNAGT~zeeD2|Cx`&P0hD2 &Ub;:63Y');
define('AUTH_SALT',        'br2=(0PU! ZKbo)qa&u6gas/ov`g{.0-!U>VNC@f@^s{>wdkY(]n7QSh2UTqF`Qv');
define('SECURE_AUTH_SALT', ' i8U*sbPxOT~v`pdY``ng+tG%5dCKOI4,<C!b(:f>gs&3+w}r|rp5.OzwnBrlMwG');
define('LOGGED_IN_SALT',   '_ahA{rpgs0eWM}5s:C3vA~0#@j!oZmqBKH5|w sX^yW%8iU>nebV+$SqKi29xv.>');
define('NONCE_SALT',       't?:%:+v|gao2.coxXF#qr% jU{Tk)tl@}=>Z&,20_c=>1K4t_^B#D.YEwy v9WfD');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
