import Auth, { AuthError } from "../auth/Auth";
import Member, { MemberAttributes } from "./Member";

type UserAttributes = MemberAttributes & {
	isLoggedIn: boolean;
	isInitializing: boolean; // True if the user is being initialized.
};

/**
 * User model.
 * Represents authenticated user.
 * This model is a singleton and should be accessed via `User.getInstance()`.
 *
 * @class User
 * @extends {Member<UserAttributes>}
 * @singleton
 *
 */
class User extends Member<UserAttributes> {
	private static instance: User | null = null;
	private auth: Auth;

	private constructor() {
		super();
		// Endpoint for the authenticated user.
		this.urlRoot = this.urlRoot + "/me";

		this.auth = new Auth();
		this.init();
	}

	public static getInstance() {
		if (!User.instance) {
			User.instance = new User();
		}
		return User.instance;
	}

	defaults(): Partial<UserAttributes> {
		return {
			isLoggedIn: false,
			isInitializing: false,
		};
	}

	private async init() {
		try {
			const currentlyLoggedInUserId = this.auth.getAuthenticatedUserId();
			// If the user is logged in, fetch the user data.
			if (currentlyLoggedInUserId) {
				this.set("isInitializing", true);

				this.set({ member_id: currentlyLoggedInUserId });
				this.set("isLoggedIn", true);
				await this.fetch();
			}
			this.set("isInitializing", false);
		} catch (error) {
			this.logout();
		}
	}

	public async login(email: string, password: string) {
		try {
			const memberId = await this.auth.login(email, password);

			this.set({ member_id: memberId });
			await this.fetch();

			this.set("isLoggedIn", true);
		} catch (error) {
			if (error instanceof AuthError) {
				throw error;
			} else {
				this.logout();
				throw new Error("Something went wrong. Please try again.");
			}
		}
	}

	async logout() {
		await this.auth.logout();
		this.set(this.defaults());
	}

	public isLoggedIn() {
		return this.get("isLoggedIn");
	}
}

export default User;
