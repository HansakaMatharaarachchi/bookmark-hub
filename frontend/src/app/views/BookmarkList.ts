import { Model, View, ViewOptions } from "backbone";
import { template } from "underscore";
import BookmarkCollection from "../collections/BookmarkCollection";
import bookmarkListTemplate from "../templates/partials/bookmark-list.html?raw";

interface BookmarkListOptions extends ViewOptions {
	collection?: BookmarkCollection;
	config?: BookmarkListConfig;
}

interface BookmarkListConfig {
	pageNumber?: number;
	perPage?: number;
	tags?: string[];
}

class BookmarksList extends View<Model> {
	private config?: BookmarkListConfig;

	private isBookmarksLoading: boolean = false;
	private isBookmarksError: boolean = false;

	collection: BookmarkCollection;

	constructor(options?: BookmarkListOptions) {
		super(options);

		const { collection, config } = options || {};

		this.config = config;
		this.collection = collection || new BookmarkCollection();

		this.listenTo(this.collection, "all", this.render);
		this.listenTo(this.collection, "request", this.onBookMarksLoadingStart);
		this.listenTo(this.collection, "sync", this.onBookMarksLoaded);
		this.listenTo(this.collection, "error", this.onBookMarksLoadingError);

		// fetch initial bookmarks according to the config.
		this.fetchBookmarks();
	}

	render() {
		this.$el.html(
			template(bookmarkListTemplate)({
				isLoading: this.isBookmarksLoading,
				isError: this.isBookmarksError,
				bookmarks: this.collection.toJSON(),
				tags: this.config?.tags,
				currentPageNumber: this.collection.getCurrentPageNumber(),
				bookmarksPerPage: this.collection.getPerPageCount(),
				totalBookmarkCount: this.collection.getTotalBookmarkCount(),
			})
		);

		return this;
	}

	public getConfig() {
		return this.config;
	}

	private fetchBookmarks() {
		this.collection.fetchPage(
			{
				tags: this.config?.tags,
			},
			this.config?.pageNumber,
			this.config?.perPage
		);
	}

	private onBookMarksLoadingStart() {
		this.isBookmarksLoading = true;
		this.isBookmarksError = false;
	}

	private onBookMarksLoaded() {
		this.isBookmarksLoading = false;
		this.isBookmarksError = false;
	}

	private onBookMarksLoadingError() {
		this.isBookmarksLoading = false;
		this.isBookmarksError = true;
	}
}

export default BookmarksList;
