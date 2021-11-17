const path = require('path');
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const CssMinimizerPlugin = require("css-minimizer-webpack-plugin");

module.exports = {
    plugins: [
        new MiniCssExtractPlugin(),
    ],
    entry: {app: ['./assets/app.js', './assets/app.css']},
    module: {
        rules: [{
                test: /\.css$/i,
                use: [ MiniCssExtractPlugin.loader, "css-loader"]
        }],
    },
    optimization: {
        minimizer: [
            // For webpack@5 you can use the `...` syntax to extend existing minimizers
            // (i.e. `terser-webpack-plugin`)
            `...`,
            new CssMinimizerPlugin(),
        ],
    },
    resolve: {
        extensions: ['.js'],
    },
    output: {
        filename: '[name].js',
        path: path.resolve(__dirname, './assets/dist'),
    },
};
