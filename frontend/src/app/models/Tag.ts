import { Model } from "backbone";

type TagAttributes = {
	tag_id?: string;
	name: string;
};

class Tag extends Model<TagAttributes> {
	idAttribute = "tag_id";

	parse(response: any) {
		return response?.data || {};
	}
}

export default Tag;
