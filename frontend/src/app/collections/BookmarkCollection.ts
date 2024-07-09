import { Collection } from "backbone";
import BookMark from "../models/Bookmark";
import { BOOKMARK_API_URL } from "../../constants";

export interface BookmarkFilters {
	search?: string;
	tags?: string[];
}

interface BookmarkCollectionOptions {
	page?: number;
	perPage?: number;
	totalBookmarkCount?: number;
}

class BookmarkCollection extends Collection<BookMark> {
	private DEFAULT_PAGE = 1;
	private DEFAULT_PER_PAGE = 10;

	private pageNumber?: number;
	private perPage?: number;
	private totalBookmarkCount?: number;

	constructor(bookmarks?: BookMark[], options?: BookmarkCollectionOptions) {
		super(bookmarks, options);
		this.model = BookMark;
		this.url = BOOKMARK_API_URL;

		this.pageNumber = options?.page ?? this.DEFAULT_PAGE;
		this.perPage = options?.perPage ?? this.DEFAULT_PER_PAGE;
		this.totalBookmarkCount = options?.totalBookmarkCount;
	}

	public fetchPage(
		filters?: BookmarkFilters,
		pageNumber: number = this.DEFAULT_PAGE,
		perPage: number = this.DEFAULT_PER_PAGE
	) {
		const offset = (pageNumber - 1) * perPage;

		return this.fetch({
			data: {
				...filters,
				tags: filters?.tags?.join(","),
				limit: perPage,
				offset,
			},
			reset: true,
		});
	}

	public getTotalBookmarkCount() {
		return this.totalBookmarkCount;
	}

	public getPerPageCount() {
		return this.perPage;
	}

	public getPageNumber() {
		return this.pageNumber;
	}

	parse(response: any) {
		const { bookmarks, total_bookmarks_count, limit, offset } =
			response.data ?? {};

		this.pageNumber = Math.floor(offset / limit) + 1;
		this.perPage = limit;
		this.totalBookmarkCount = total_bookmarks_count;

		return bookmarks.map((bookmark: any) => new BookMark(bookmark));
	}
}

export default BookmarkCollection;
