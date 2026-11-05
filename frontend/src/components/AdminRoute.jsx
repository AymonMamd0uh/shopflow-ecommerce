import { useEffect, useState } from "react";
import { Navigate, Outlet } from "react-router-dom";
import api from "../services/api";

export default function AdminRoute() {
  const [status, setStatus] = useState("loading");

  useEffect(() => {
    const checkAdmin = async () => {
      const token = localStorage.getItem("token");

      if (!token) {
        setStatus("guest");
        return;
      }

      try {
        const response = await api.get("/auth/me");

        const user =
          response.data.data?.user ||
          response.data.data ||
          response.data.user;

        setStatus(user?.role === "admin" ? "admin" : "forbidden");
      } catch {
        localStorage.removeItem("token");
        setStatus("guest");
      }
    };

    checkAdmin();
  }, []);

  if (status === "loading") {
    return (
      <div className="flex min-h-screen items-center justify-center">
        <p className="font-semibold text-slate-500">
          Checking permissions...
        </p>
      </div>
    );
  }

  if (status === "guest") {
    return <Navigate to="/login" replace />;
  }

  if (status === "forbidden") {
    return <Navigate to="/" replace />;
  }

  return <Outlet />;
}