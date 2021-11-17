const path = require('path');
const MiniCssExtractPlugin = require("mini-css-extract-plugin");

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
    resolve: {
        extensions: ['.js'],
    },
    output: {
        filename: '[name].js',
        path: path.resolve(__dirname, './assets/dist'),
    },
};
