/** @type {import('tailwindcss').Config} */
export default {
	content: ["./src/**/*.{html,ts}"],
	theme: {
		extend: {
			colors: {
				primary: "#FF7844",
				"primary-ash": "#F5F5F5",
				"primary-text": "#EEEEEE",
				"placeholder-text": "RGBA(25,24,37,0.5)",
				"input-text": "RGBA(25,24,37,0.75)",
			},
			fontFamily: {
				sans: ["circular-std", "sans-serif"],
			},
		},
	},
	plugins: [],
};
