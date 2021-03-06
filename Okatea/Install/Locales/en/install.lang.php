<?php
/*
 * This file is part of Okatea.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

# common
$GLOBALS['okt_l10n']['i_install_interface'] = 'Installation interface';
$GLOBALS['okt_l10n']['i_update_interface'] = 'Update interface';
$GLOBALS['okt_l10n']['i_errors'] = 'Error(s)';

# steps
$GLOBALS['okt_l10n']['i_step_start'] = 'home';
$GLOBALS['okt_l10n']['i_step_checks'] = 'pre-requisites';
$GLOBALS['okt_l10n']['i_step_db_conf'] = 'database';
$GLOBALS['okt_l10n']['i_step_db'] = 'tables';
$GLOBALS['okt_l10n']['i_step_supa'] = 'users';
$GLOBALS['okt_l10n']['i_step_theme'] = 'theme';
$GLOBALS['okt_l10n']['i_step_colors'] = 'colors';
$GLOBALS['okt_l10n']['i_step_pages'] = 'pages';
$GLOBALS['okt_l10n']['i_step_merge_config'] = 'merge configuration';
$GLOBALS['okt_l10n']['i_step_end'] = 'end';

# start
$GLOBALS['okt_l10n']['i_start_about_install'] = 'You are about to <strong>install</strong> Okatea %s.';
$GLOBALS['okt_l10n']['i_start_about_update'] = 'You are about to <strong>update</strong> Okatea to <em>%s</em> version.';

$GLOBALS['okt_l10n']['i_start_choose_lang'] = 'You can choose the language of the interface:';
$GLOBALS['okt_l10n']['i_start_click_next'] = 'To continue please click the "Next" button below.';

# checks
$GLOBALS['okt_l10n']['i_checks_title'] = 'Checking pre-requisites';
$GLOBALS['okt_l10n']['i_checks_warning'] = '<strong>Warning:</strong> the system audit issued alerts did not prevent the system from working but it is possible that some features are failing.';
$GLOBALS['okt_l10n']['i_checks_big_loose'] = 'The configuration server has major problems. The system can not be installed on this server.';

# db conf
$GLOBALS['okt_l10n']['i_db_conf_title'] = 'Connecting to the database';

$GLOBALS['okt_l10n']['i_db_conf_create_db_ok'] = 'The database was created.';
$GLOBALS['okt_l10n']['i_db_conf_create_db_ko'] = 'Unable to create the database.';

$GLOBALS['okt_l10n']['i_db_conf_connection_file_ok'] = 'The connection file was created.';
$GLOBALS['okt_l10n']['i_db_conf_connection_file_ko'] = 'Unable to create the connection file.';

$GLOBALS['okt_l10n']['i_db_conf_conn_ok'] = 'Connection to database successful.';
$GLOBALS['okt_l10n']['i_db_conf_conn_ko'] = 'Failed to connect to the database.';

$GLOBALS['okt_l10n']['i_db_conf_next'] = 'Click Next to create the tables.';

$GLOBALS['okt_l10n']['i_db_conf_driver'] = 'Please select the database driver to use for this installation:';
$GLOBALS['okt_l10n']['i_db_conf_driver_show_unsupported'] = 'Display drivers currently not supported on your environment';

$GLOBALS['okt_l10n']['i_db_conf_driver_pdo_mysql'] = 'A MySQL driver that uses the pdo_mysql PDO extension.';
$GLOBALS['okt_l10n']['i_db_conf_driver_drizzle_pdo_mysql'] = 'A Drizzle driver that uses pdo_mysql PDO extension.';
$GLOBALS['okt_l10n']['i_db_conf_driver_mysqli'] = 'A MySQL driver that uses the mysqli PHP extension.';
$GLOBALS['okt_l10n']['i_db_conf_driver_pdo_sqlite'] = 'An SQLite driver that uses the pdo_sqlite PDO extension.';
$GLOBALS['okt_l10n']['i_db_conf_driver_pdo_pgsql'] = 'A PostgreSQL driver that uses the pdo_pgsql PDO extension.';
$GLOBALS['okt_l10n']['i_db_conf_driver_pdo_oci'] = 'An Oracle driver that uses the pdo_oci PDO extension. Note that this driver can cause problems, opt for the oci8 driver if possible.';
$GLOBALS['okt_l10n']['i_db_conf_driver_pdo_sqlsrv'] = 'A Microsoft SQL Server driver that uses pdo_sqlsrv PDO. Note that this driver can cause problems, opt for the sqlsrv driver if possible.';
$GLOBALS['okt_l10n']['i_db_conf_driver_sqlsrv'] = 'A Microsoft SQL Server driver that uses the sqlsrv PHP extension.';
$GLOBALS['okt_l10n']['i_db_conf_driver_oci8'] = 'An Oracle driver that uses the oci8 PHP extension.';
$GLOBALS['okt_l10n']['i_db_conf_driver_sqlanywhere'] = 'A SAP Sybase SQL Anywhere driver that uses the sqlanywhere PHP extension.';

$GLOBALS['okt_l10n']['i_db_conf_environement_choice'] = 'Test the connection on the environment:';
$GLOBALS['okt_l10n']['i_db_conf_environement_prod'] = 'production';
$GLOBALS['okt_l10n']['i_db_conf_environement_dev'] = 'development';
$GLOBALS['okt_l10n']['i_db_conf_environement_note'] = 'You must choose the environment in which you are installing the system.';
$GLOBALS['okt_l10n']['i_db_conf_prod_server'] = 'Production server';
$GLOBALS['okt_l10n']['i_db_conf_dev_server'] = 'Development server';

$GLOBALS['okt_l10n']['i_db_conf_db_prefix'] = 'Database table prefix';
$GLOBALS['okt_l10n']['i_db_conf_db_host'] = 'Hostname of the database to connect to';
$GLOBALS['okt_l10n']['i_db_conf_db_port'] = 'Port of the database to connect to';
$GLOBALS['okt_l10n']['i_db_conf_db_name'] = 'Name of the database/schema to connect to';
$GLOBALS['okt_l10n']['i_db_conf_db_username'] = 'Username to use when connecting to the database';
$GLOBALS['okt_l10n']['i_db_conf_db_password'] = 'Password to use when connecting to the database';
$GLOBALS['okt_l10n']['i_db_conf_db_unix_socket'] = 'Password to use when connecting to the database';
$GLOBALS['okt_l10n']['i_db_conf_db_charset'] = 'The charset used when connecting to the database.';
$GLOBALS['okt_l10n']['i_db_conf_db_mysqli_flags'] = 'Any supported flags for mysqli found on http://www.php.net/manual/en/mysqli.real-connect.php';
$GLOBALS['okt_l10n']['i_db_conf_db_sqlite_path'] = 'The filesystem path to the database file. Mutually exclusive with memory. path takes precedence.';
$GLOBALS['okt_l10n']['i_db_conf_db_sqlite_memory'] = 'True if the SQLite database should be in-memory (non-persistent). Mutually exclusive with path. path takes precedence.';

$GLOBALS['okt_l10n']['i_db_conf_db_error_must_prefix'] = 'You must enter a table prefix for the database.';
$GLOBALS['okt_l10n']['i_db_conf_db_error_prefix_form'] = 'The table prefix is invalid. It can only contain lowercase letters and "_" character.';
$GLOBALS['okt_l10n']['i_db_conf_db_error_prod_must_host'] = 'You must enter a host database for the production environment.';
$GLOBALS['okt_l10n']['i_db_conf_db_error_dev_must_host'] = 'You must enter a host database for the development environment.';
$GLOBALS['okt_l10n']['i_db_conf_db_error_prod_must_name'] = 'You must enter a database for the production environment.';
$GLOBALS['okt_l10n']['i_db_conf_db_error_dev_must_name'] = 'You must enter a database for the development environment.';
$GLOBALS['okt_l10n']['i_db_conf_db_error_prod_must_username'] = 'You must enter a username database for the production environment.';
$GLOBALS['okt_l10n']['i_db_conf_db_error_dev_must_username'] = 'You must enter a username database for the development environment.';

# connexion
$GLOBALS['okt_l10n']['i_connexion_title'] = 'Connecting to the database';
$GLOBALS['okt_l10n']['i_connexion_success'] = 'Connecting to the database successfully. Click next to update the tables.';

# db tables
$GLOBALS['okt_l10n']['i_db_tables_title'] = 'Creating tables of the database';
$GLOBALS['okt_l10n']['i_db_warning'] = '<strong>Warning:</strong> the system audit issued alerts but this should not pose any problems.';
$GLOBALS['okt_l10n']['i_db_big_loose'] = 'Fatal errors occurred, unable to continue the installation.';

# supa
$GLOBALS['okt_l10n']['i_supa_title'] = 'Create administrator accounts';
$GLOBALS['okt_l10n']['i_supa_account_sudo'] = 'Super-administrator account';
$GLOBALS['okt_l10n']['i_supa_account_sudo_note'] = 'The super administrator account is the account that has full permissions. It’s you :)';
$GLOBALS['okt_l10n']['i_supa_account_admin'] = 'Administrator account';
$GLOBALS['okt_l10n']['i_supa_account_admin_note'] = 'The administrator account is an account that has permissions by default, but not all. It can provide access to the website administration but not all features. Useful for example to allow another person to manage the website or just having a clean interface for managing everyday. This account is optional, it can be created later if needed.';
$GLOBALS['okt_l10n']['i_supa_username'] = 'Username';
$GLOBALS['okt_l10n']['i_supa_password'] = 'Password';
$GLOBALS['okt_l10n']['i_supa_email'] = 'Email address';
$GLOBALS['okt_l10n']['i_supa_must_sudo_username'] = 'You must enter a username for the super-administrator account.';
$GLOBALS['okt_l10n']['i_supa_must_admin_username'] = 'You must enter a username for the administrator account.';
$GLOBALS['okt_l10n']['i_supa_must_sudo_password'] = 'You must enter a password for the super-administrator account.';
$GLOBALS['okt_l10n']['i_supa_must_admin_password'] = 'You must enter a password for the administrator account.';
$GLOBALS['okt_l10n']['i_supa_must_sudo_email'] = 'You must enter an email address for the super-administrator account.';
$GLOBALS['okt_l10n']['i_supa_must_admin_email'] = 'You must enter an email address for the administrator account.';

# theme
$GLOBALS['okt_l10n']['i_theme_title'] = 'Theme choice';

# colors
$GLOBALS['okt_l10n']['i_colors_title'] = 'Theme colors';

# pages
$GLOBALS['okt_l10n']['i_pages_title'] = 'Creation of the first pages';
$GLOBALS['okt_l10n']['i_pages_no_module_pages'] = 'The pages module is not installed, you can not create a page.';
$GLOBALS['okt_l10n']['i_pages_page_title_%s'] = 'Title of page %s';
$GLOBALS['okt_l10n']['i_pages_page_content_%s'] = 'Content of page %s';
$GLOBALS['okt_l10n']['i_pages_page_home_%s'] = 'Set page %s as homepage';
$GLOBALS['okt_l10n']['i_pages_page_no_home'] = 'No homepage yet';
$GLOBALS['okt_l10n']['i_pages_add_one_more'] = 'Add one more page';
$GLOBALS['okt_l10n']['i_pages_first_home_title'] = 'Home';
$GLOBALS['okt_l10n']['i_pages_first_home_content'] = "Welcom to our new website.\n\nThis website is currently under construction, thank you to return later reference.";
$GLOBALS['okt_l10n']['i_pages_first_about_title'] = 'About';
$GLOBALS['okt_l10n']['i_pages_first_default_content'] = 'This website is currently under construction, thank you to return later reference.';

# merge config
$GLOBALS['okt_l10n']['i_merge_config_title'] = 'Merging configuration data';
$GLOBALS['okt_l10n']['i_merge_config_done'] = 'The configuration data were merged successfully.';
$GLOBALS['okt_l10n']['i_merge_config_not'] = 'The configuration data have not been merged.';

# end
$GLOBALS['okt_l10n']['i_end_install_title'] = 'This is the end... of the installation';
$GLOBALS['okt_l10n']['i_end_update_title'] = 'This is the end... of the update';

$GLOBALS['okt_l10n']['i_end_install_congrat'] = 'Congratulations!You have successfully installed the system.';
$GLOBALS['okt_l10n']['i_end_update_congrat'] = 'Congratulations!You have successfully updated the system.';

$GLOBALS['okt_l10n']['i_end_connect'] = 'Log into <a href="%s">the administration interface</a> to configure the system.';
