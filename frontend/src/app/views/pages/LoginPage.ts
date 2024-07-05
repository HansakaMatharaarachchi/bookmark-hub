import { history } from "backbone";
import Swal from "sweetalert2";
import { template } from "underscore";
import loginTemplate from "../../templates/pages/login.html?raw";
import BasePage, { BasePageOptions } from "./BasePage";

class LoginPage extends BasePage {
	constructor(options?: BasePageOptions) {
		super(options);

		this.setTitle("Login");
	}

	events() {
		return {
			"click #submit-btn": "handleSubmitButtonClick",
		};
	}

	render() {
		super.render();

		this.$el.html(template(loginTemplate)());
		return this;
	}

	private handleSubmitButtonClick(e: JQuery.ClickEvent) {
		e.preventDefault();

		const email = this.$("#email").val() as string;
		const password = this.$("#password").val() as string;

		if (!email || !password) {
			Swal.fire({
				icon: "error",
				title: "Error",
				text: "Please fill out all fields.",
			});

			return;
		}

		Swal.fire({
			title: "Logging in...",
			text: "Please wait while we log you in.",
			allowOutsideClick: false,
			allowEnterKey: false,
			allowEscapeKey: false,
			didOpen: () => {
				Swal.showLoading();
			},
		});

		this.user
			?.login(email, password)
			.then(async () => {
				Swal.close();

				await Swal.fire({
					icon: "success",
					title: "Success",
					text: "You have successfully logged in.",
					timer: 2000,
				});

				history.navigate("/", { replace: true, trigger: true });
			})
			.catch((error) => {
				Swal.close();

				Swal.fire({
					icon: "error",
					title: "Oops!",
					text: error.message,
				});
			});
	}
}

export default LoginPage;
