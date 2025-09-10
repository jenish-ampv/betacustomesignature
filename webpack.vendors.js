module.exports = {
	output: 'dist/assets',
	entry: {
		keenicons: [
			{
				src: ['src/vendors/keenicons/**/style.css'],
				dist: '/vendors/keenicons/styles.bundle.css',
				bundle: true,
			},
			{
				src: [
					'src/vendors/keenicons/duotone/fonts',
					'src/vendors/keenicons/filled/fonts',
					'src/vendors/keenicons/outline/fonts',
					'src/vendors/keenicons/solid/fonts',
				],
				dist: '/vendors/keenicons/fonts',
			},
		],
		switzer: [
			{
				src: ['src/vendors/switzer/fonts.css'],
				dist: '/vendors/switzer/fonts.css',
				bundle: true,
			},
			{
				src: [
					'src/vendors/switzer/fonts/Switzer Thin.eot',
					'src/vendors/switzer/fonts/Switzer Thin.woff2',
					'src/vendors/switzer/fonts/Switzer Thin.woff',
					'src/vendors/switzer/fonts/Switzer Thin.svg',
					'src/vendors/switzer/fonts/Switzer Light.eot',
					'src/vendors/switzer/fonts/Switzer Light.woff2',
					'src/vendors/switzer/fonts/Switzer Light.woff',
					'src/vendors/switzer/fonts/Switzer Light.svg',
					'src/vendors/switzer/fonts/Switzer Regular.eot',
					'src/vendors/switzer/fonts/Switzer Regular.woff2',
					'src/vendors/switzer/fonts/Switzer Regular.woff',
					'src/vendors/switzer/fonts/Switzer Regular.svg',
					'src/vendors/switzer/fonts/Switzer Medium.eot',
					'src/vendors/switzer/fonts/Switzer Medium.woff2',
					'src/vendors/switzer/fonts/Switzer Medium.woff',
					'src/vendors/switzer/fonts/Switzer Medium.svg',
					'src/vendors/switzer/fonts/Switzer Semibold.eot',
					'src/vendors/switzer/fonts/Switzer Semibold.woff2',
					'src/vendors/switzer/fonts/Switzer Semibold.woff',
					'src/vendors/switzer/fonts/Switzer Semibold.svg',
					'src/vendors/switzer/fonts/Switzer Bold.eot',
					'src/vendors/switzer/fonts/Switzer Bold.woff2',
					'src/vendors/switzer/fonts/Switzer Bold.woff',
					'src/vendors/switzer/fonts/Switzer Bold.svg',
					'src/vendors/switzer/fonts/Switzer Extrabold.eot',
					'src/vendors/switzer/fonts/Switzer Extrabold.woff2',
					'src/vendors/switzer/fonts/Switzer Extrabold.woff',
					'src/vendors/switzer/fonts/Switzer Extrabold.svg',
					'src/vendors/switzer/fonts/Switzer Black.eot',
					'src/vendors/switzer/fonts/Switzer Black.woff2',
					'src/vendors/switzer/fonts/Switzer Black.woff',
					'src/vendors/switzer/fonts/Switzer Black.svg',
				],
				dist: '/vendors/switzer/fonts',
			},
		],
		'@form-validation': [
			{
				src: ['src/vendors/@form-validation/umd/styles'],
				dist: '/vendors/@form-validation',
			},
			{
				src: [
					'src/vendors/@form-validation/umd/bundle/popular.min.js',
					'src/vendors/@form-validation/umd/bundle/full.min.js',
					'src/vendors/@form-validation/umd/plugin-bootstrap5/index.min.js',
				],
				dist: '/vendors/@form-validation/form-validation.bundle.js',
				bundle: true,
			},
		],
		leaflet: [
			{
				src: ['node_modules/leaflet/dist/leaflet.css'],
				dist: '/vendors/leaflet/leaflet.bundle.css',
				bundle: true,
			},
			{
				src: [
					'node_modules/leaflet/dist/leaflet.js',
					'node_modules/esri-leaflet/dist/esri-leaflet.js',
					'node_modules/esri-leaflet-geocoder/dist/esri-leaflet-geocoder.js',
				],
				dist: '/vendors/leaflet/leaflet.bundle.js',
				bundle: true,
			},
			{
				src: ['node_modules/leaflet/dist/images'],
				dist: '/vendors/leaflet/images',
			},
		],
		apexcharts: [
			{
				src: ['node_modules/apexcharts/dist/apexcharts.css'],
				dist: '/vendors/apexcharts/apexcharts.css',
			},
			{
				src: ['node_modules/apexcharts/dist/apexcharts.min.js'],
				dist: '/vendors/apexcharts/apexcharts.min.js',
			},
		],
		prismjs: [
			{
				src: [
					'node_modules/prismjs/prism.js',
					'node_modules/prismjs/components/prism-markup.js',
					'node_modules/prismjs/components/prism-markup-templating.js',
					'node_modules/prismjs/components/prism-bash.js',
					'node_modules/prismjs/components/prism-javascript.js',
					'node_modules/prismjs/components/prism-css.js',
					'node_modules/prismjs/plugins/normalize-whitespace/prism-normalize-whitespace.js',
					'src/vendors/prismjs/prismjs.init.js',
				],
				dist: '/vendors/prismjs/prismjs.min.js',
				bundle: true,
			},
		],
		clipboard: [
			{
				src: ['node_modules/clipboard/dist/clipboard.min.js'],
				dist: '/vendors/clipboard/clipboard.min.js',
			},
		],
		ktui: [
			{
				src: ['node_modules/@keenthemes/ktui/dist/ktui.min.js'],
				dist: '/vendors/ktui/ktui.min.js',
			},
		],
		swiper: [
			{
			  src: [
				'node_modules/swiper/swiper-bundle.min.css'
			  ],
			  dist: '/vendors/swiper/swiper.bundle.css',
			  bundle: true,
			},
			{
			  src: [
				'node_modules/swiper/swiper-bundle.min.js'
			  ],
			  dist: '/vendors/swiper/swiper.bundle.js',
			  bundle: true,
			}
		  ],
		  sweetalert: [
			{
				src: [
				'node_modules/sweetalert2/dist/sweetalert2.min.css'
				],
				dist: '/vendors/sweetalert2/sweetalert2.bundle.css',
				bundle: true,
			},
			{
				src: [
				'node_modules/sweetalert2/dist/sweetalert2.all.min.js'
				],
				dist: '/vendors/sweetalert2/sweetalert2.bundle.js',
				bundle: true,
			}
		],
	},
};
