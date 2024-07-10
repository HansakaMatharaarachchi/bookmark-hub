import { Model, ModelSaveOptions } from "backbone";
import moment, { Moment } from "moment";
import { isEmpty, isString } from "underscore";
import { BOOKMARK_API_URL } from "../../constants";
import TagCollection from "../collections/TagCollection";

export type BookmarkAttributes = {
	bookmark_id?: string;
	member_id?: string;
	title: string;
	url: string;
	tags: TagCollection;
	created_at?: Moment;
};

class BookMark extends Model<Partial<BookmarkAttributes>> {
	urlRoot = BOOKMARK_API_URL;
	idAttribute = "bookmark_id";

	constructor(attributes?: BookmarkAttributes) {
		super(attributes);

		if (attributes?.created_at) {
			this.set(
				"created_at",
				moment.isMoment(attributes.created_at)
					? attributes.created_at
					: moment(attributes.created_at)
			);
		}
		if (attributes?.tags) {
			this.set(
				"tags",
				attributes?.tags instanceof TagCollection
					? attributes.tags
					: new TagCollection(attributes?.tags)
			);
		}
	}

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
		} else {
			const invalidTags = attributes.tags.filter((tag) => !tag.isValid());

			if (invalidTags.length > 0) {
				errors.tags = "Tag names must be less than 50 characters";
			}

			// check if there are duplicated tags.
			const tagNames = attributes.tags.pluck("name");
			const uniqueTagNames = new Set(tagNames);

			if (tagNames.length !== uniqueTagNames.size) {
				errors.tags = "Duplicate tags are not allowed";
			}
		}
		return Object.keys(errors).length > 0 ? errors : undefined;
	}

	toJSON() {
		const json = super.toJSON();

		if (json.tags && json.tags instanceof TagCollection) {
			json.tags = json.tags.toJSON();
		}

		return json;
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
