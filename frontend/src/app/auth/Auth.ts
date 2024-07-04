import { ajax, ajaxPrefilter, Deferred, noop } from "jquery";
import { jwtDecode } from "jwt-decode";
import { AUTH_API_URL, BASE_API_URL } from "../../constants";

export class AuthError extends Error {
	constructor(message: string) {
		super(message);
		this.name = "AuthError";
	}
}

// Handles user authentication.
class Auth {
	private accessToken: string | null;
	// The maximum number of times to retry a request after the access token has expired.
	private maxExpiredAuthorizationRetries = 3;

	constructor() {
		this.accessToken = this.getTokenFromStorage();
		this.addAuthInterceptor();
	}

	/**
	 * Logs in a user and returns the member ID.
	 * @param email The user's email.
	 * @param password The user's password.
	 * @returns A promise that resolves with the member ID on successful login.
	 * @throws {AuthError} If the login fails.
	 */
	public async login(email: string, password: string): Promise<string> {
		return new Promise((resolve, reject) => {
			ajax({
				url: `${AUTH_API_URL}/login`,
				method: "POST",
				xhrFields: {
					withCredentials: true,
				},
				data: { email, password },
				dataType: "json",
				success: (response) => {
					const { access_token } = response.data;
					const member_id = this.extractMemberIdFromToken(access_token);

					if (member_id) {
						this.accessToken = access_token;
						this.storeTokenInStorage(access_token);

						resolve(member_id);
					} else {
						reject(new AuthError("Login failed. Please try again."));
					}
				},
				error: (error) => {
					let errorMessage = "Something went wrong. Please try again later.";

					switch (error?.status) {
						case 401:
							errorMessage = "Invalid email or password.";
							break;
						case 404:
							errorMessage = "User not found.";
							break;
						case 422:
							errorMessage = "Email and password are required.";
							break;
					}

					reject(new AuthError(errorMessage));
				},
			});
		});
	}

	/**
	 * Logs out the user.
	 * @returns A promise that resolves on successful logout.
	 * @throws {AuthError} If the logout fails.
	 */
	public async logout() {
		if (this.accessToken) {
			return new Promise<void>((resolve, reject) => {
				ajax({
					url: `${AUTH_API_URL}/logout`,
					method: "POST",
					success: () => {
						this.removeTokenFromStorage();

						// Reload the page to clear any cached data.
						location.reload();

						resolve();
					},
					error: () => {
						reject(new AuthError("Logout failed. Please try again."));
					},
				});
			});
		}
	}

	/**
	 * Returns the member ID of the currently authenticated user.
	 * @returns The member ID of the currently authenticated user, or null if no user is authenticated.
	 */
	public getAuthenticatedUserId() {
		if (this.accessToken) {
			return this.extractMemberIdFromToken(this.accessToken);
		}
		return null;
	}

	/**
	 * Refreshes the access token.
	 * @returns A promise that resolves on successful token refresh.
	 * @throws {AuthError} If the token refresh fails.
	 */
	private async refreshToken() {
		return new Promise<void>((resolve, reject) => {
			// @ts-ignore
			ajax({
				url: `${AUTH_API_URL}/refresh_token`,
				method: "POST",
				xhrFields: {
					withCredentials: true,
				},
				// When refreshing the access token, no need use the auth interceptor.
				skipAuthInterceptor: true,
				success: (response) => {
					const { access_token } = response.data;

					this.accessToken = access_token;
					this.storeTokenInStorage(access_token);

					resolve();
				},
				error: () => {
					reject(new AuthError("Failed to refresh token."));
				},
			});
		});
	}

	/**
	 * Extracts the member ID from the provided access token.
	 * @param access_token The JWT access token.
	 * @returns The member ID extracted from the token.
	 */
	private extractMemberIdFromToken(access_token: string) {
		try {
			const decodedToken = jwtDecode<{ sub: string }>(access_token);

			return decodedToken.sub;
		} catch (error) {
			return null;
		}
	}

	/**
	 * Adds an authentication interceptor to all AJAX requests.
	 * This interceptor adds the access token to the request headers.
	 * If the request fails due to an expired access token, it refreshes the token and retries the request.
	 * If the refresh token request fails, it logs out the user.
	 * If the request fails for any other reason, it rejects the request.
	 * @see https://stackoverflow.com/questions/11793430/retry-a-jquery-ajax-request-which-has-callbacks-attached-to-its-deferred
	 */
	private addAuthInterceptor() {
		// @ts-ignore
		ajaxPrefilter((options, originalOptions: any, jqXHR) => {
			// Only intercept API requests that are sent to the backend.
			if (
				options.url?.startsWith(BASE_API_URL) &&
				!originalOptions.skipAuthInterceptor
			) {
				// Add the access token to the request headers.
				jqXHR.setRequestHeader(
					"Authorization",
					`Bearer ${this.accessToken ?? ""}`
				);

				// If the request fails due to an expired access token, refresh the token and retry the request.
				if (this.refreshToken) {
					// Prevent infinite recursion.
					originalOptions._retry = isNaN(originalOptions._retry)
						? this.maxExpiredAuthorizationRetries
						: originalOptions._retry - 1;

					// save the original error callback for later.
					if (originalOptions.error) {
						originalOptions._error = originalOptions.error;
					}

					// Override current request error callback.
					options.error = noop();

					// Setup a deferred object to handle retries.
					const dfd = Deferred();

					jqXHR.done(dfd.resolve);

					// if the request fails, do something else yet still resolve.
					jqXHR.fail(async () => {
						const args = [...arguments];

						if (jqXHR.status === 401 && originalOptions._retry > 0) {
							try {
								await this.refreshToken();
							} catch (error) {
								// If the refresh token request fails, log out the user.
								await this.logout();
							}

							// re-send the original request with the new refreshed token.
							ajax(originalOptions).then(dfd.resolve, dfd.reject);
						} else {
							// add our _error callback to our promise object
							if (originalOptions._error) {
								dfd.fail(originalOptions._error);
							}

							dfd.rejectWith(jqXHR, args);
						}
					});

					// NOW override the jqXHR's promise functions with our deferred
					return dfd.promise(jqXHR);
				}
			}
		});
	}

	/**
	 * Retrieves the stored access token from localStorage.
	 *
	 * @returns The stored access token, or null if it doesn't exist.
	 */
	private getTokenFromStorage() {
		return localStorage.getItem("jat");
	}

	/**
	 * Stores the access token in localStorage.
	 * @param token The access token to store.
	 */
	private storeTokenInStorage(token: string) {
		localStorage.setItem("jat", token);
	}

	/**
	 * Removes the access token from localStorage.
	 */
	private removeTokenFromStorage() {
		localStorage.removeItem("jat");
	}
}

export default Auth;
