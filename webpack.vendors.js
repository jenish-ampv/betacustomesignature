module.exports = {
	output: 'dist/assets',
	entry: {
		
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
		intlTelInput: [
			{
				src: [
				'node_modules/intl-tel-input/build/css/intlTelInput.css'
				],
				dist: '/vendors/intl-tel-input/css/intlTelInput.css',
				bundle: true,
			},
			{
				src: [
				'node_modules/intl-tel-input/build/js/intlTelInput.min.js'
				],
				dist: '/vendors/intl-tel-input/js/intlTelInput.js',
				bundle: true,
			},
			{
				src: [
				'node_modules/intl-tel-input/build/js/utils.js'
				],
				dist: '/vendors/intl-tel-input/js/utils.js',
				bundle: false,
			},
			{
				src: [
				'node_modules/intl-tel-input/build/img/flags.png',
				'node_modules/intl-tel-input/build/img/flags.webp',
				'node_modules/intl-tel-input/build/img/flags@2x.png',
				'node_modules/intl-tel-input/build/img/flags@2x.webp',
				],
				dist: '/vendors/intl-tel-input/img',
			}
		],
	},
};
