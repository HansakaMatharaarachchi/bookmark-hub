import { Router, history } from "backbone";
import $ from "jquery";
import { PageName } from "./pageConfig";

// Define routes.
const routes = {
	"": "login",
	signup: "signup",
	login: "login",
	bookmarks: "bookmarks",
	"bookmarks/add": "addBookmark",
	"bookmarks/:bookmarkId": "bookmark",
	"bookmarks/:bookmarkId/edit": "editBookmark",
	// "404": "notFound",
	// "401": "unauthorized",
	// "*path": "notFound",
};

type AppRouterOptions = {
	renderPage: (pageName: PageName, params?: {}) => void;
};

class AppRouter extends Router {
	private renderPage;

	constructor({ renderPage, ...options }: AppRouterOptions) {
		super({ ...options, routes });
		this.handleNavLinkNavigation();

		this.renderPage = renderPage;
	}

	signup() {
		this.renderPage("signup");
	}

	login() {
		this.renderPage("login");
	}

	// notFound() {
	// 	this.renderPage("notFound");
	// }

	// unauthorized() {
	// 	this.renderPage("unauthorized");
	// }

	// Handle navigation using backbone history API.
	// !important: In order to work, anchor tags must have the class "nav-link".
	private handleNavLinkNavigation() {
		$(document).on("click", "a[href].nav-link ", (e) => {
			e.preventDefault();

			const href = $(e.currentTarget).attr("href");
			if (href != null) {
				history.navigate(href, { trigger: true });
			}
		});
	}
}

export { AppRouter };
