import api from "./api";

export  const login = (credentials) => {
    return api.post("/auth", credentials);
}

export const register = (data) => {
    return api.post("/auth", data);
}

export const logout = () => {
    return api.post("/logout");
}

export const getCurrentUser = () => {
    return api.get("/auth/me");
}