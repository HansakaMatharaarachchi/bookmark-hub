import { Collection } from "backbone";
import Tag from "../models/Tag";

class TagCollection extends Collection<Tag> {
	model = Tag;

	parse(response: any) {
		return response.data?.map((tagData: any) => new Tag(tagData));
	}
}

export default TagCollection;
