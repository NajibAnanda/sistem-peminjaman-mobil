import { Toaster as Sonner, type ToasterProps } from "sonner"
import { CircleAlert, CircleCheck, Info, Loader2, TriangleAlert } from "lucide-react"
import { useAppearance } from "@/hooks/use-appearance"

// Diubah dari preset: tema diambil dari pengaturan tampilan aplikasi, bukan
// next-themes (tidak ada ThemeProvider di aplikasi ini).
const Toaster = ({ ...props }: ToasterProps) => {
  const { appearance } = useAppearance()

  return (
    <Sonner
      theme={appearance}
      className="toaster group"
      icons={{
        success: <CircleCheck className="size-5 text-card-foreground" />,
        info: <Info className="size-5 text-card-foreground" />,
        warning: <TriangleAlert className="size-5 text-card-foreground" />,
        error: <CircleAlert className="size-5 text-card-foreground" />,
        loading: <Loader2 className="size-5 text-card-foreground animate-spin" />,
      }}
      style={
        {
          "--normal-bg": "var(--card)",
          "--normal-text": "var(--card-foreground)",
          "--normal-border": "var(--border)",
          "--border-radius": "var(--radius)",
        } as React.CSSProperties
      }
      toastOptions={{
        classNames: {
          toast:
            "group toast group-[.toaster]:bg-card group-[.toaster]:text-card-foreground group-[.toaster]:border-border/80 group-[.toaster]:shadow-lg group-[.toaster]:rounded-xl group-[.toaster]:p-4 group-[.toaster]:gap-3 group-[.toaster]:font-sans",
          title: "font-semibold text-card-foreground text-sm tracking-tight",
          description: "!text-muted-foreground text-xs leading-relaxed",
          icon: "self-start mt-0.5",
          actionButton:
            "bg-primary text-primary-foreground font-medium rounded-lg text-xs px-3 py-1.5",
          cancelButton:
            "bg-muted text-muted-foreground font-medium rounded-lg text-xs px-3 py-1.5",
          closeButton:
            "border-border text-muted-foreground hover:text-foreground",
        },
      }}
      {...props}
    />
  )
}

export { Toaster }
