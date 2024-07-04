import { Model, View, ViewOptions } from "backbone";
import Swal from "sweetalert2";
import User from "../../models/User";

export interface BasePageOptions<TModel extends Model = Model>
	extends ViewOptions<TModel> {
	params?: Record<string, any>;
}

class BasePage<TModel extends Model = Model> extends View<Model> {
	protected isLoading;
	protected user: User | null;

	constructor(options?: BasePageOptions<TModel>) {
		super(options);
		this.isLoading = false;
		this.user = User.getInstance();

		this.listenTo(this.user, "change:isInitializing", this.onLoggedInUserInit);
	}

	protected setTitle(title: string) {
		document.title = title;
	}

	protected async showLoader(): Promise<void> {
		if (this.isLoading) return;
		this.isLoading = true;

		await Swal.fire({
			title: "Loading...",
			text: "Please wait.",
			allowOutsideClick: false,
			allowEnterKey: false,
			allowEscapeKey: false,
			didOpen: () => {
				Swal.showLoading();
			},
		});
	}

	protected hideLoader() {
		Swal.close();
		if (!this.isLoading) return;
		this.isLoading = false;
	}

	remove() {
		this.hideLoader();
		return super.remove();
	}

	private onLoggedInUserInit() {
		if (this.user?.get("isInitializing")) {
			this.showLoader();
		} else {
			this.hideLoader();
		}
	}
}

export default BasePage;
