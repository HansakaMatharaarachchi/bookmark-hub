import { Model, ModelSaveOptions } from "backbone";
import { isEmpty, isString } from "underscore";
import { BOOKMARK_API_URL } from "../../constants";
import TagCollection from "../collections/TagCollection";

export type BookmarkAttributes = {
	bookmark_id?: string;
	member_id?: string;
	title: string;
	url: string;
	tags: TagCollection;
	created_at?: string;
};

class BookMark extends Model<Partial<BookmarkAttributes>> {
	urlRoot = BOOKMARK_API_URL;
	idAttribute = "bookmark_id";

	validate(attributes: Partial<BookmarkAttributes>) {
		const errors: Record<string, string> = {};

		if (!attributes.title?.trim()) {
			errors.title = "Title is required";
		} else if (!this.isValidTitle(attributes.title)) {
			errors.title = "Title must be less than 150 characters";
		}

		if (!attributes.url?.trim()) {
			errors.url = "Url is required";
		} else if (!this.isValidURL(attributes.url)) {
			errors.url = "URL must be less than 2083 characters";
		}

		if (
			!(attributes.tags instanceof TagCollection) ||
			attributes.tags.isEmpty()
		) {
			errors.tags = "At least one tag is required";
		}

		return Object.keys(errors).length > 0 ? errors : undefined;
	}

	save(
		attributes?: Partial<BookmarkAttributes> | null,
		options?: ModelSaveOptions
	) {
		const attrsToSave = {
			...(isEmpty(attributes) ? this.attributes : attributes),
		};

		const currentTagCollection = this.get("tags");

		// Convert the tags collection to an array of tag names.
		// This is necessary because the server expects an array of tag names not a collection of tags.
		if (attrsToSave?.tags && attrsToSave.tags instanceof TagCollection) {
			attrsToSave.tags = attrsToSave.tags.pluck("name") as any;
		}

		const successCallback = options?.success;
		const errorCallback = options?.error;

		// Override the success and error callbacks to reset the tags attribute on success or error.
		options = {
			...options,
			validate: false,
			wait: true,
			success: (model, response, options) => {
				this.set("tags", currentTagCollection, { silent: true });

				// Call the original success callback.
				successCallback?.call(this, model, response, options);
			},
			error: (model, response, options) => {
				this.set("tags", currentTagCollection, { silent: true });

				// Call the original error callback.
				errorCallback?.call(this, model, response, options);
			},
		};

		return super.save(attrsToSave, options);
	}

	private isValidTitle = (title: string) => {
		return isString(title) && title.length <= 150;
	};

	private isValidURL = (url: string) => {
		return isString(url) && url.length <= 2083;
	};
}

export default BookMark;
