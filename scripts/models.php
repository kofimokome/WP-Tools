<?php


/**
 * Creates a model file in the models folder
 *
 * @param string $model_name the name of the model
 *
 * @author kofimokome
 */
function make_model( string $model_name, string $table_name = '' ): void {
	global $env;
	global $plugin_root_dir;
	if ( ! isset( $env['MODELS_DIR'] ) ) {
		echo( "\033[31mERROR!: The models directory is not set. Please set it in the .env file\n" );
		exit();
	}
	// check if the models folder exists
	$models_dir = $plugin_root_dir . $env['MODELS_DIR'];

	if ( ! is_dir( $models_dir ) ) {
		echo( "\033[31mERROR!: The models directory {$env['MIGRATIONS_DIR']} does not exist. Please create it first.\n" );
		exit();
	}

	$models_dir = rtrim( $models_dir, '/' );
	$model_name = str_replace( '-', '_', $model_name );

	// replace any symbol in the model name with _
	$model_name = preg_replace( '/[^A-Za-z0-9\-]/', '_', $model_name );

	// convert the model name to a valid class name eg User
	$model_class_name = str_replace( '_', ' ', $model_name );
	$model_class_name = ucwords( $model_class_name );
	$model_class_name = str_replace( ' ', '', $model_class_name );

	$file_path = $models_dir . '/' . $model_class_name . '.php';
	$file      = fopen( $file_path, 'w' );
	$namespace = clean_input( $env['NAMESPACE'] );

	$model_template = '<?php 
namespace ' . $namespace . ';

use KMModel;

class ' . $model_class_name . ' extends KMModel {';
	if ( $table_name != '' ) {
		$model_template .= '
	protected $table_name = "' . $table_name . '";';
	}
	$model_template .= '
}

';
	fwrite( $file, $model_template );
	fclose( $file );
	echo( "\033[32m\033[1mModel $file_path created successfully! \033[0m \033[39m \n" );
}
