import { history, View, ViewOptions } from "backbone";
import $ from "jquery";
import Swal from "sweetalert2";
import { template } from "underscore";
import User from "../../models/User";
import navbarTemplate from "../../templates/layouts/navbar.html?raw";

type NavBarOptions = ViewOptions & {
	user: User | null;
};

class NavBar extends View {
	private user: User | null;

	constructor(options?: NavBarOptions) {
		super(options);
		this.user = options?.user || null;

		// Close the profile dropdown when the user clicks outside it.
		$(document).on("click", this.closeProfileDropdown.bind(this));
	}

	events() {
		return {
			"click #profile": "toggleProfileOptions",
			"click #delete-account-btn": "deleteAccount",
			"click #sign-out-btn": "logOut",
		};
	}

	render() {
		super.render();

		this.$el.html(template(navbarTemplate)());
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

	private toggleProfileOptions() {
		this.$("#profile-options").toggleClass("hidden");
	}

	private closeProfileDropdown(event: JQuery.ClickEvent) {
		if (!event.target.closest("#profile-container")) {
			this.$("#profile-options").addClass("hidden");
		}
	}

	private async deleteAccount() {
		if (this.user?.isLoggedIn()) {
			Swal.fire({
				title: "Are you sure?",
				text: "You will not be able to recover your account!",
				icon: "warning",
				showCancelButton: true,
				confirmButtonText: "Yes, delete it!",
				cancelButtonText: "No, cancel!",
				showLoaderOnConfirm: true,
			}).then(async (result) => {
				if (result.isConfirmed) {
					await this.user?.destroy();
				}
			});
		}
	}

	private async logOut() {
		if (this.user?.isLoggedIn()) {
			Swal.fire({
				title: "Are you sure?",
				text: "You will be signed out!",
				icon: "warning",
				showCancelButton: true,
				confirmButtonText: "Yes, sign out!",
				cancelButtonText: "No, cancel!",
				showLoaderOnConfirm: true,
			}).then(async (result) => {
				if (result.isConfirmed) {
					await this.user?.logout();
				}
			});
		}
	}
}

export default NavBar;
