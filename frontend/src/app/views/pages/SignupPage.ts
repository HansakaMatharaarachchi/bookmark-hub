import { history, Model } from "backbone";
import Swal from "sweetalert2";
import { template } from "underscore";
import Member from "../../models/Member";
import signupTemplate from "../../templates/pages/signup.html?raw";
import BasePage, { BasePageOptions } from "./BasePage";

class SignupPage extends BasePage {
	constructor(options?: BasePageOptions) {
		super(options);
		this.setTitle("Sign Up");

		this.model = new Member();
	}

	events() {
		return {
			"change input.form-field": "handleInputChange",
			"click #submit-btn": "handleSubmitButtonClick",
		};
	}

	render() {
		this.$el.html(template(signupTemplate)());
		this.toggleSubmitButtonState();

		return this;
	}

	private handleInputChange(event: JQuery.ChangeEvent<HTMLInputElement>) {
		const fieldName = event.target.name;
		const fieldValue = event.target.value;

		this.updateModel(fieldName, fieldValue);
		this.displayValidationMessage(fieldName);
		this.toggleSubmitButtonState();
	}

	private updateModel(fieldName: string, fieldValue: string) {
		this.model.set({ [fieldName]: fieldValue });
	}

	private displayValidationMessage(fieldName: string) {
		this.model.isValid();
		const errorMessage = this.model.validationError?.[fieldName];

		this.$(`#error-${fieldName}`).text(
			errorMessage ? `Please enter a valid ${fieldName}.` : ""
		);
	}

	// Toggle submit button state based on validation.
	private toggleSubmitButtonState() {
		const hasErrors = !this.model.isValid();
		const $submitBtn = this.$("#submit-btn");

		$submitBtn
			.prop("disabled", hasErrors)
			.toggleClass("cursor-not-allowed opacity-50", hasErrors)
			.attr(
				"title",
				hasErrors
					? "Please fill all required fields providing valid information."
					: ""
			);
	}

	private handleSubmitButtonClick(e: JQuery.ClickEvent) {
		e.preventDefault();

		if (this.model.isValid()) {
			Swal.fire({
				title: "Signing up...",
				text: "Please wait while we sign you up.",
				allowOutsideClick: false,
				allowEnterKey: false,
				allowEscapeKey: false,
				didOpen: () => {
					Swal.showLoading();
				},
			});

			this.model.save(
				{},
				{
					// wait for the server to respond before updating the model.
					wait: true,
					success: () => {
						Swal.close();

						Swal.fire({
							title: "Account created!",
							text: "Please log in to continue.",
							icon: "success",
							confirmButtonText: "OK",
						}).then(() => {
							history.navigate("login", {
								replace: true,
								trigger: true,
							});
						});
					},
					error: (_model: Model, response: JQueryXHR) => {
						Swal.close();

						if (response?.status === 409) {
							Swal.fire({
								title: "Email already exists",
								text: "Please use a different email address or log in.",
								icon: "error",
								confirmButtonText: "OK",
							});
						} else {
							Swal.fire({
								title: "Oops! Something went wrong.",
								text: "Please try again later.",
								icon: "error",
								confirmButtonText: "OK",
							});
						}
					},
				}
			);
		}
	}
}

export default SignupPage;
