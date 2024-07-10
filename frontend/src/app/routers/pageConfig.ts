import BasePage from "../views/pages/BasePage";
import BookmarksPage from "../views/pages/BookmarksPage";
import NotFoundPage from "../views/pages/error/NotFound";
import LoginPage from "../views/pages/LoginPage";
import SignupPage from "../views/pages/SignupPage";

type Pages = {
	[key: string]: {
		page: typeof BasePage;
		requiredAuth?: boolean;
	};
};

export const pages: Pages = {
	signup: {
		page: SignupPage,
	},
	login: {
		page: LoginPage,
	},
	bookmarks: {
		page: BookmarksPage,
		requiredAuth: true,
	},
	notFound: {
		page: NotFoundPage,
	},
};

export type PageName = keyof typeof pages;
