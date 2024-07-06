import { Model } from "backbone";

type TagAttributes = {
	tag_id?: string;
	name: string;
};

class Tag extends Model<TagAttributes> {
	idAttribute = "tag_id";

	validate(attributes: Partial<TagAttributes>) {
		const errors: Record<string, string> = {};

		if (this.isValidName(attributes.name)) {
			errors.name =
				"Name is required and must be less than 50 characters with no leading or trailing spaces";
		}

		return Object.keys(errors).length > 0 ? errors : undefined;
	}

	parse(response: any) {
		return response?.data || {};
	}

	private isValidName(name: unknown) {
		if (typeof name !== "string") {
			return false;
		}

		const trimmedName = name.trim();

		return (
			trimmedName.length > 0 && trimmedName.length <= 50 && trimmedName === name
		);
	}
}

export default Tag;
