import { history } from "backbone";
import { AppRouter } from "./app/routers/AppRouter";

import $ from "jquery";
import User from "./app/models/User";
import { PageName, pages } from "./app/routers/pageConfig";
import BasePage from "./app/views/pages/BasePage";
import "./assets/styles/main.css";

const user = User.getInstance();
let currentPage: BasePage;

const renderPage = (pageName: PageName, params = {}) => {
	const { page: Page, requiredAuth } = pages[pageName];

	if (!Page) return;

	if (requiredAuth && !user.isLoggedIn()) {
		history.navigate("/signup", { trigger: true });
		return;
	}

	// Redirect to home page if trying to access login/signup page while already logged in.
	if (user.isLoggedIn() && (pageName === "login" || pageName === "signup")) {
		router.navigate("/", { replace: true, trigger: true });
		return;
	}

	// Clean up the previous page.
	if (currentPage) {
		currentPage.remove();
	}

	const newPage = new Page({ params });

	// Render the new page.
	$("#app").append(newPage.render().el);

	currentPage = newPage;
};

// Initialize the router.
const router = new AppRouter({
	renderPage,
});

// Start listening to routes.
history.start({ pushState: true });
