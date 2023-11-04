<?php


/**
 * Creates a migration file in the migrations folder
 *
 * @param string $migration_name the name of the migration
 *
 * @author kofimokome
 */
function make_migration( string $migration_name, $table_name = '', $is_update = false ): void {
	global $env;
	global $plugin_root_dir;
	if ( ! isset( $env['MIGRATIONS_DIR'] ) ) {
		echo("\033[31mERROR!: The migrations directory is not set. Please set it in the .env file\033[39m \n");
		exit();
	}
	// check if the migrations folder exists
	$migrations_dir = $plugin_root_dir . $env['MIGRATIONS_DIR'];

	if ( ! is_dir( $migrations_dir ) ) {
		echo("\033[31mERROR!: The migrations directory {$env['MIGRATIONS_DIR']} does not exist. Please create it first\033[39m \n");
		exit();
	}
	$migrations_dir = rtrim( $migrations_dir, '/' );
	$migration_name = str_replace( '-', '_', $migration_name );

	// replace any symbol in the migration name with _
	$migration_name = preg_replace( '/[^A-Za-z0-9\-]/', '_', $migration_name );

	// create a file in the migration folder with the syntax YYYY_MM_DD_HHMMSS_migration_name.php
	$file_name = date( 'Y_m_d_His' ) . '_' . $migration_name . '.php';
	$file_path = $migrations_dir . '/' . $file_name;
	$file      = fopen( $file_path, 'w' );

	// convert the migration name to a valid class name eg from add_test_to_users_table to AddTestToUsersTable
	$migration_class_name = str_replace( '_', ' ', $migration_name );
	$migration_class_name = ucwords( $migration_class_name );
	$migration_class_name = str_replace( ' ', '', $migration_class_name );
	$namespace            = clean_input( $env['NAMESPACE'] );

	if ( $is_update ) {
		$migration_template = '<?php 
namespace ' . $namespace . ';

use KMBlueprint;
use KMMigration;

class ' . $migration_class_name . ' extends KMMigration {
	protected  $table_name = "' . $table_name . '";
	protected  $is_update = true;

	public function up( KMBlueprint $blueprint ) {
		// $blueprint->string( "slug", 100 );
	}

	public function down( KMBlueprint $blueprint ) {
		// $blueprint->dropColumn( "slug" );
	}
}

';
	} else {
		$migration_template = '<?php 
namespace ' . $namespace . ';

use KMBlueprint;
use KMMigration;

class ' . $migration_class_name . ' extends KMMigration {
	protected  $table_name = "' . $table_name . '";

	public function up( KMBlueprint $blueprint ) {
		$blueprint->id();
		// your migrations here
	}

	public function down( KMBlueprint $blueprint ) {
		$blueprint->drop();
	}
}

';
	}
	fwrite( $file, $migration_template );
	fclose( $file );
	echo( "\033[32m\033[1mMigration $file_path created successfully! \033[0m \033[39m \n" );
}
