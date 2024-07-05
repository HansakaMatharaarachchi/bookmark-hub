import { Collection } from "backbone";
import BookMark from "../models/Bookmark";

class BookmarkCollection extends Collection<BookMark> {
	model = BookMark;

	parse(response: any) {
		return response.data?.map((tagData: any) => new BookMark(tagData));
	}
}

export default BookmarkCollection;
