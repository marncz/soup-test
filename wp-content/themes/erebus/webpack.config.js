// Webpack Config for JS, SCSS, Images, Fonts & SVG's
const webpack = require('webpack');
const path = require('path');
const TerserPlugin = require('terser-webpack-plugin');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const FixStyleOnlyEntriesPlugin = require('webpack-fix-style-only-entries');
const globImporter = require('node-sass-glob-importer');
const CleanWebpackPlugin = require('clean-webpack-plugin');
const ImageminPlugin = require('imagemin-webpack-plugin').default;
const ManifestPlugin = require('webpack-manifest-plugin');
const BrowserSyncPlugin = require('browser-sync-webpack-plugin');
const Dotenv = require('dotenv-webpack');
const WebpackBar = require('webpackbar');

module.exports = (env, argv) => {
    const devMode = argv.mode === 'development';

    return {
        entry: {
            main: ['./assets/js/main.js'],
            style: ['./assets/scss/style.scss'],
            editor: [
                './assets/scss/editor-style.scss',
                './assets/js/editor.js'
            ]
        },
        output: {
            filename: devMode ? '[name].js' : '[name].[hash].min.js',
            path: path.resolve(__dirname, './public/dist/')
        },
        devtool: 'source-maps',
        module: {
            rules: [
                {
                    test: /\.js$/,
                    exclude: /node_modules/,
                    use: {
                        loader: 'babel-loader',
                        options: {
                            presets: [
                                ['@babel/preset-env']
                            ],
                            plugins: [
                                '@babel/plugin-proposal-export-default-from',
                                '@babel/plugin-proposal-class-properties'
                            ]
                        }
                    }
                },
                {
                    test: /\.s?css$/,
                    use: [
                        MiniCssExtractPlugin.loader,
                        {
                            loader: 'css-loader',
                            options: { sourceMap: true }
                        },
                        {
                            loader: 'postcss-loader',
                            options: { sourceMap: true }
                        },
                        { loader: 'resolve-url-loader' },
                        {
                            loader: 'sass-loader',
                            options: {
                                sourceMap: true,
                                importer: globImporter()
                            }
                        }
                    ]
                },
                {
                    test: /\.(png|jpg|gif)$/,
                    use: [
                        {
                            loader: 'file-loader',
                            options: {
                                outputPath: 'images/',
                                name: '[name].[ext]'
                            }
                        }
                    ]
                },
                {
                    test: /\.(svg)$/,
                    use: [
                        {
                            loader: 'file-loader',
                            options: {
                                outputPath: 'svgs/',
                                name: '[name].[ext]'
                            }
                        },
                        {
                            loader: 'svgo-loader',
                            options: {
                                plugins: [
                                    { removeTitle: true },
                                    { convertColors: { shorthex: false } },
                                    { convertPathData: false },
                                    { removeViewBox: false }
                                ]
                            }
                        }
                    ]
                },
                {
                    test: /\.(woff(2)?|ttf|eot)$/,
                    use: [
                        {
                            loader: 'file-loader',
                            options: {
                                outputPath: 'fonts/',
                                name: '[name].[ext]'
                            }
                        }
                    ]
                }
            ]
        },
        optimization: {
            minimize: true,
            minimizer: [
                new TerserPlugin({
                    parallel: true,
                    terserOptions: { output: { comments: false } }
                })
            ]
        },
        plugins: [
            new CleanWebpackPlugin(),
            new FixStyleOnlyEntriesPlugin(),
            new MiniCssExtractPlugin({
                filename: devMode ? '[name].css' : '[name].[hash].min.css',
                chunkFilename: devMode ? '[id].css' : '[id].[hash].min.css'
            }),
            new ImageminPlugin({
                test: /\.(jpg|png|gif)$/i,
                cacheFolder: './imgcache'
            }),
            new ManifestPlugin(),
            new webpack.ProvidePlugin({
                $: 'jquery',
                jQuery: 'jquery',
                'window.jQuery': 'jquery'
            }),
            new BrowserSyncPlugin(
                {
                    // browse to http://localhost:3000/ during development
                    host: 'localhost',
                    port: 3000,
                    // proxy the Webpack Dev Server endpoint
                    // through BrowserSync via php server
                    proxy: 'http://localhost:8080/',
                    notify: true,
                    injectCss: true,
                    files: ['./public/dist/*.css'],
                    open: false
                },
                {
                    // prevent BrowserSync from reloading the page
                    // and let Webpack Dev Server take care of this
                    reload: false
                }
            ),
            new Dotenv({
                path: '../../../.env',
                systemvars: true
            }),
            new WebpackBar()
        ]
    };
};
