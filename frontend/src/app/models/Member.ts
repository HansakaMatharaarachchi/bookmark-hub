import { Model, ModelSaveOptions } from "backbone";
import { MEMBER_API_URL } from "../../constants";
import { isString, isEmpty } from "underscore";

export type MemberAttributes = {
	member_id: string;
	nickname: string;
	email?: string;
	password?: string;
	created_at?: string;
};

class Member<T extends MemberAttributes = MemberAttributes> extends Model<T> {
	urlRoot = MEMBER_API_URL;
	idAttribute = "member_id";

	validate(attributes: Partial<T>) {
		const errors: Record<string, string> = {};

		if (!this.isValidNickname(attributes.nickname)) {
			errors.nickname = "Nickname is invalid.";
		}

		// When creating a new member, the email and password are required.
		if (!this.get("member_id") && !this.isValidEmail(attributes.email)) {
			errors.email = "Email is invalid.";
		}

		if (!this.get("member_id") && !this.isValidPassword(attributes.password)) {
			errors.password = "Password is invalid.";
		}

		return !isEmpty(errors) ? errors : undefined;
	}

	save(attributes?: Partial<T> | null | undefined, options?: ModelSaveOptions) {
		const successCallback = options?.success;

		options = {
			...options,
			success: (model, response, options) => {
				// Omit the email and password from the model after saving.
				model.omit("email", "password");
				successCallback?.call(this, model, response, options);
			},
		};

		return super.save(attributes, options);
	}

	parse(response: any) {
		return response?.data || {};
	}

	isValidNickname(nickname: unknown) {
		return (
			isString(nickname) &&
			/^(?=.*[a-zA-Z0-9])[a-zA-Z0-9_ ]{1,50}$/.test(nickname)
		);
	}

	isValidEmail(email: unknown) {
		return (
			isString(email) &&
			// https://stackoverflow.com/questions/46155/how-can-i-validate-an-email-address-in-javascript.
			RegExp(
				/^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|.(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
			).exec(String(email).toLowerCase())
		);
	}

	isValidPassword(password: unknown) {
		return (
			isString(password) &&
			/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[@#$%^&+=])(?=\S+$).{8,}$/.test(
				password
			)
		);
	}
}

export default Member;
