import BasePage, { BasePageOptions } from "./BasePage";
import NavBar from "../layouts/Navbar";

class BookmarksPage extends BasePage {
	private navbar = new NavBar({
		user: this.user,
	});

	constructor(options?: BasePageOptions) {
		super(options);

		this.setTitle("Bookmarks");
	}

	render() {
		super.render();

		// Render the navbar.
		this.$el.append(this.navbar.render().$el);

		return this;
	}
}

export default BookmarksPage;
