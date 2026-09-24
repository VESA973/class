import {
  CircleCheckIcon,
  InfoIcon,
  Loader2Icon,
  OctagonXIcon,
  TriangleAlertIcon,
} from "lucide-react"
import { useEffect, useState } from "react"
import { Toaster as Sonner, type ToasterProps } from "sonner"

// Le site n'utilise pas next-themes : on suit la classe "dark" posee sur <html>.
function useDocumentTheme(): ToasterProps["theme"] {
  const read = () => (document.documentElement.classList.contains("dark") ? "dark" : "light")
  const [theme, setTheme] = useState<ToasterProps["theme"]>(read)

  useEffect(() => {
    const observer = new MutationObserver(() => setTheme(read()))
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ["class"] })

    return () => observer.disconnect()
  }, [])

  return theme
}

const Toaster = ({ ...props }: ToasterProps) => {
  const theme = useDocumentTheme()

  return (
    <Sonner
      theme={theme}
      className="toaster group"
      icons={{
        success: <CircleCheckIcon className="size-4" />,
        info: <InfoIcon className="size-4" />,
        warning: <TriangleAlertIcon className="size-4" />,
        error: <OctagonXIcon className="size-4" />,
        loading: <Loader2Icon className="size-4 animate-spin" />,
      }}
      style={
        {
          "--normal-bg": "var(--ui-popover)",
          "--normal-text": "var(--ui-popover-foreground)",
          "--normal-border": "var(--ui-border)",
          "--border-radius": "var(--ui-radius)",
        } as React.CSSProperties
      }
      {...props}
    />
  )
}

export { Toaster }
