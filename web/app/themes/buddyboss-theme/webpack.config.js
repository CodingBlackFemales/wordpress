const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path          = require( "path" );

module.exports = {
	...defaultConfig,
	entry: {
		'buddypanel': './blocks/buddypanel/src',
	},
	output: {
		path: path.resolve( __dirname, 'blocks/buddypanel/build/' ),
		filename: '[name].js'
	}
};
