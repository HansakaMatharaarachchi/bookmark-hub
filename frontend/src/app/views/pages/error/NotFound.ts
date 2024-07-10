import { template } from "underscore";
import notFoundTemplate from "../../../templates/pages/error/not-found.html?raw";
import BasePage, { BasePageOptions } from "../BasePage";

class NotFoundPage extends BasePage {
	constructor(options?: BasePageOptions) {
		super(options);

		this.setTitle("Not Found");
	}

	render() {
		super.render();

		this.$el.html(template(notFoundTemplate)());
		return this;
	}
}

export default NotFoundPage;
