module.exports = {
	module: {
		rules: [
			{
				test: /\.js$/,
				use: [
					{
						loader: 'babel-loader',
						options: {
							babelrc: true,
						},
					},
				],
			},
			{
				test: /\.svg$/,
				use: [
					{
						loader: 'file-loader',
					},
				],
			},
		],
	},
	externals: {
		lodash: 'lodash',
		'@wordpress/data': 'wp.data',
		'@wordpress/element': 'wp.element',
	},
};
