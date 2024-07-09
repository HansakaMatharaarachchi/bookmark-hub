import { history } from "backbone";
import $ from "jquery";
import Swal from "sweetalert2";
import { template } from "underscore";
import BookmarkCollection from "../../collections/BookmarkCollection";
import TagCollection from "../../collections/TagCollection";
import BookMark from "../../models/Bookmark";
import Tag from "../../models/Tag";
import bookmarksPageTemplate from "../../templates/pages/bookmarks.html?raw";
import bookmarkFormTemplate from "../../templates/partials/bookmark-form.html?raw";
import bookmarViewTemplate from "../../templates/partials/bookmark.html?raw";
import BookmarkListView from "../BookmarkList";
import NavBar from "../layouts/Navbar";
import BasePage, { BasePageOptions } from "./BasePage";

interface BookmarkPageOptions extends BasePageOptions {
	params?: {
		// Filters to fetch bookmarks.
		filters?: {
			pageNumber: number;
			tags?: string[];
		};
	};
}

class BookmarksPage extends BasePage {
	private navbar = new NavBar({
		user: this.user,
	});
	private bookmarkListView: BookmarkListView;

	collection: BookmarkCollection;

	constructor(options?: BookmarkPageOptions) {
		super({
			...options,
			className: "bookmark-page",
		});

		this.setTitle("BookmarkHub");
		this.collection = new BookmarkCollection();

		const { pageNumber = 1, tags = [] } = options?.params?.filters ?? {};

		this.bookmarkListView = new BookmarkListView({
			collection: this.collection,
			config: {
				pageNumber,
				tags,
			},
		});
	}

	events() {
		return {
			"click #add-new-bookmark-btn": "onAddNewBookmarkBtnClick",
			"click #search-bookmarks-btn": "onSearchBookmarksBtnClick",
			"click .bookmark": "renderBookmark", // TODO fix tag url not working on double click on cards.
		};
	}

	render() {
		super.render();

		// Render the navbar.
		this.$el.append(this.navbar.render().$el);

		// Render the bookmarks page.
		this.$el.append(template(bookmarksPageTemplate)());
		// Set the search input value, if any.
		this.$("#search-bookmarks-input").val(
			this.bookmarkListView.getConfig()?.tags?.join(", ") ?? ""
		);

		// Render the bookmark list.
		this.bookmarkListView.setElement(this.$("#bookmark-list")).render();

		return this;
	}

	private async onAddNewBookmarkBtnClick() {
		await this.renderBookMarkForm();
	}

	private async onSearchBookmarksBtnClick(event: Event) {
		event.preventDefault();

		const tagsNeedBeFiltered = this.$("#search-bookmarks-input")
			.val()
			?.toString()
			.split(",")
			.reduce((acc: string[], tag: string) => {
				const trimmedTag = tag.trim();

				if (trimmedTag) {
					acc.push(trimmedTag);
				}
				return acc;
			}, []);

		if (tagsNeedBeFiltered?.length) {
			// Redirect to the bookmarks page with the tags filter.
			history.navigate(`bookmarks?tags=${tagsNeedBeFiltered.join(",")}`, {
				trigger: true,
			});
		}
	}

	// Render the bookmark form to add/edit a bookmark.
	private async renderBookMarkForm(bookmark?: BookMark, onCancel?: () => void) {
		bookmark = new BookMark(bookmark?.toJSON() ?? {});

		const isCreatingNew = bookmark.isNew();

		await Swal.fire({
			title: `${isCreatingNew ? "Add" : "Edit"} Bookmark`,
			showCancelButton: true,
			confirmButtonText: `${isCreatingNew ? "Add" : "Edit"}`,
			showLoaderOnConfirm: true,
			allowEscapeKey: false,
			allowOutsideClick: false,
			html: template(bookmarkFormTemplate)({
				bookmark: bookmark.toJSON(),
			}),
			didRender: () => {
				$(".swal2-confirm")
					.prop("disabled", !bookmark.isValid())
					.attr(
						"title",
						bookmark.isValid() ? "" : "Please fill all the required fields."
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

						bookmark.set({ [fieldName]: fieldValue });

						const isFormValid = bookmark.isValid();

						const errorMessage = bookmark.validationError?.[fieldName];
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
					if (bookmark.isValid()) {
						if (isCreatingNew) {
							await bookmark.save();
						} else {
							await bookmark.save(null, {
								patch: true,
							});
						}

						this.collection.fetch();
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
		}).then(async (result) => {
			if (result.isConfirmed) {
				await Swal.fire({
					title: `${isCreatingNew ? "Added" : "Edited"}!`,
					text: `Bookmark has been ${
						isCreatingNew ? "added" : "edited"
					} successfully.`,
					icon: "success",
					showConfirmButton: false,
					timer: 2000,
				});
			} else {
				onCancel?.();
			}
		});
	}

	private async deleteBookmark(bookmark: BookMark) {
		try {
			await bookmark.destroy({
				wait: true,
			});

			return true;
		} catch (exception) {
			return false;
		}
	}

	private renderBookmark(event: JQuery.ClickEvent) {
		event.preventDefault();

		if (event.currentTarget) {
			const bookmarkId = this.$(event.currentTarget).data("bookmark-id");
			const bookmarkToBeRendered = this.collection.get(bookmarkId);

			Swal.fire({
				showCloseButton: true,
				html: template(bookmarViewTemplate)({
					bookmark: bookmarkToBeRendered.toJSON(),
				}),
				showConfirmButton: false,
				didRender: () => {
					$("#edit-bookmark-btn").on("click", async () => {
						await this.renderBookMarkForm(bookmarkToBeRendered, () =>
							this.renderBookmark(event)
						);
					});
					$("#delete-bookmark-btn").on("click", async () => {
						Swal.fire({
							title: "Are you sure?",
							text: "You won't be able to revert this!",
							icon: "warning",
							showCancelButton: true,
							confirmButtonText: "Yes, delete it!",
							cancelButtonText: "No, cancel!",
						}).then(async (result) => {
							if (result.isConfirmed) {
								Swal.showLoading();
								if (await this.deleteBookmark(bookmarkToBeRendered)) {
									Swal.fire({
										title: "Deleted!",
										text: "Bookmark has been deleted successfully.",
										icon: "success",
										showConfirmButton: false,
										timer: 2000,
									});

									// Fetch the bookmarks again.
									this.collection.fetch();
								} else {
									Swal.showValidationMessage(
										"Failed to delete bookmark, please try again later."
									);
								}
							} else {
								// Re-render the bookmark view.
								this.renderBookmark(event);
							}
						});
					});
				},
			});
		}
	}
}

export default BookmarksPage;
