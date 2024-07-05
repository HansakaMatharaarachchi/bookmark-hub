import { history } from "backbone";
import $ from "jquery";
import Swal from "sweetalert2";
import { template } from "underscore";
import TagCollection from "../../collections/TagCollection";
import BookMark from "../../models/Bookmark";
import Tag from "../../models/Tag";
import bookmarksPageTemplate from "../../templates/pages/bookmarks.html?raw";
import bookmarkFormTemplate from "../../templates/partials/bookmark-form.html?raw";
import NavBar from "../layouts/Navbar";
import BasePage, { BasePageOptions } from "./BasePage";
interface BookmarkPageOptions extends BasePageOptions {
	params?: {
		// Filters to fetch bookmarks.
		filters?: {
			page: number;
			tags?: string[];
		};
	};
}

class BookmarksPage extends BasePage {
	private navbar = new NavBar({
		user: this.user,
	});

	constructor(options?: BookmarkPageOptions) {
		super({
			...options,
			className: "bookmark-page",
		});
		this.setTitle("BookmarkHub");
	}

	events() {
		return {
			"click #add-new-bookmark-btn": "onAddNewBookmarkBtnClick",
		};
	}

	render() {
		super.render();

		// Render the bookmarks page.
		this.$el.html(template(bookmarksPageTemplate)());

		// Render the navbar.
		this.$el.prepend(this.navbar.render().$el);

		return this;
	}

	private async onAddNewBookmarkBtnClick() {
		await this.renderBookMarkForm();
	}

	// Render the bookmark form to add/edit a bookmark.
	private async renderBookMarkForm(bookMark = new BookMark()) {
		const isCreatingNew = bookMark.isNew();

		await Swal.fire({
			title: `${isCreatingNew ? "Add" : "Edit"} Bookmark`,
			showCancelButton: true,
			confirmButtonText: `${isCreatingNew ? "Add" : "Edit"}`,
			showLoaderOnConfirm: true,
			allowEscapeKey: false,
			allowOutsideClick: false,
			html: template(bookmarkFormTemplate)({
				bookmark: bookMark.toJSON(),
			}),
			didRender: () => {
				$(".swal2-confirm")
					.prop("disabled", !bookMark.isValid())
					.attr(
						"title",
						bookMark.isValid() ? "" : "Please fill all the required fields."
					);

				$("input.bookmark-form-field").on("input", (event: any) => {
					const fieldName = event.target?.name;
					let fieldValue = event.target?.value;

					if (fieldName) {
						if (fieldName === "tags") {
							fieldValue = fieldValue
								?.split(",")
								.reduce((acc: TagCollection, tag: string) => {
									const trimmedTag = tag.trim();

									if (trimmedTag) {
										acc.add(new Tag({ name: trimmedTag }));
									}
									return acc;
								}, new TagCollection());
						}

						bookMark.set({ [fieldName]: fieldValue });

						const isFormValid = bookMark.isValid();

						const errorMessage = bookMark.validationError?.[fieldName];
						$(`#error-${fieldName}`).text(errorMessage ?? "");

						// Enable/Disable the submit button.
						$(".swal2-confirm")
							.prop("disabled", !isFormValid)
							.attr(
								"title",
								isFormValid ? "" : "Please fill all the required fields."
							);
					}
				});
			},
			preConfirm: async () => {
				try {
					if (bookMark.isValid()) {
						if (isCreatingNew) {
							await bookMark.save(null, {
								wait: true,
							});
						} else {
							await bookMark.save(null, {
								wait: true,
								patch: true,
							});
						}
					}
				} catch {
					// Show the error message.
					Swal.showValidationMessage(
						`Failed to ${
							isCreatingNew ? "add" : "edit"
						} bookmark, please try again later.`
					);
				}
			},
		});
	}

	private renderBookMark(bookmark: BookMark) {
		Swal.fire({
			html: template(bookmarkFormTemplate)({
				bookmark: bookmark.toJSON(),
			}),
		});
	}

	private async fetchExistingBookmarkById(id: string) {
		try {
			await this.showLoader();

			const existingQuestion = new BookMark({
				bookmark_id: id,
			});

			return await existingQuestion.fetch();
		} catch (e) {
			const error = e as any;

			// TODO add 404, 403, 500 error pages.
			if (error.status === 404 || error.status === 403) {
				history.navigate(`/${error.status}`, {
					replace: true,
					trigger: true,
				});
			} else {
				history.navigate("500", {
					replace: true,
					trigger: true,
				});
			}
		} finally {
			this.hideLoader();
		}
	}
}

export default BookmarksPage;
