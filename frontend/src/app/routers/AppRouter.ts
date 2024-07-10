import { Router, history } from "backbone";
import $ from "jquery";
import { PageName } from "./pageConfig";

// Define routes.
const routes = {
	"": "bookmarks",
	signup: "signup",
	login: "login",
	bookmarks: "bookmarks",
	"404": "notFound",
	"*path": "notFound",
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

	bookmarks() {
		const queryParams = new URLSearchParams(window.location.search);

		const pageNumber = parseInt(queryParams.get("page") ?? "1");
		const tags = queryParams
			?.get("tags")
			?.split(",")
			.reduce((acc: string[], tag: string) => {
				const trimmedTag = tag.trim();

				if (trimmedTag) {
					acc.push(trimmedTag);
				}
				return acc;
			}, []);

		// Pass the query param values to the bookmarks page.
		this.renderPage("bookmarks", {
			filters: {
				pageNumber,
				tags,
			},
		});
	}

	notFound() {
		this.renderPage("notFound");
	}

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
