import BasePage from "../views/pages/BasePage";
import LoginPage from "../views/pages/LoginPage";

type Pages = {
	[key: string]: {
		page: typeof BasePage;
		requiredAuth?: boolean;
	};
};

export const pages: Pages = {
	login: {
		page: LoginPage,
	},
};

export type PageName = keyof typeof pages;
