import { history } from "backbone";
import { AppRouter } from "./app/routers/AppRouter";

import User from "./app/models/User";
import { PageName, pages } from "./app/routers/pageConfig";
import "./assets/styles/main.css";

const user = User.getInstance();

const renderPage = (pageName: PageName, params = {}) => {
	const { page: Page, requiredAuth } = pages[pageName];

	if (!Page) return;

	if (requiredAuth && !user.isLoggedIn()) {
		history.navigate("/login", { trigger: true });
		return;
	}

	// Redirect to home page if trying to access login/signup page while already logged in.
	if (user.isLoggedIn() && (pageName === "login" || pageName === "signup")) {
		router.navigate("/", { replace: true, trigger: true });
		return;
	}

	// Render the page.
	new Page({ ...params, el: "#app" }).render();
};

// Initialize the router.
const router = new AppRouter({
	renderPage,
});

// Start listening to routes.
history.start({ pushState: true });
