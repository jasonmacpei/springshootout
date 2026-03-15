import * as React from "react";
import { cva, type VariantProps } from "class-variance-authority";

import { cn } from "@/lib/utils";

const buttonVariants = cva(
  "inline-flex items-center justify-center rounded-full text-sm font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50",
  {
    variants: {
      variant: {
        default: "bg-[var(--accent)] px-5 py-2.5 text-[var(--accent-foreground)] hover:opacity-90",
        ghost: "px-4 py-2 text-[var(--foreground)] hover:bg-black/5",
        outline:
          "border border-[var(--border-strong)] px-5 py-2.5 text-[var(--foreground)] hover:bg-[var(--surface)]",
        secondary: "bg-[var(--surface-strong)] px-5 py-2.5 text-[var(--foreground)] hover:opacity-90",
      },
      size: {
        default: "",
        sm: "px-3 py-2 text-xs",
        lg: "px-6 py-3 text-base",
      },
    },
    defaultVariants: {
      variant: "default",
      size: "default",
    },
  },
);

export interface ButtonProps
  extends React.ButtonHTMLAttributes<HTMLButtonElement>,
    VariantProps<typeof buttonVariants> {}

export const Button = React.forwardRef<HTMLButtonElement, ButtonProps>(
  ({ className, variant, size, ...props }, ref) => (
    <button className={cn(buttonVariants({ variant, size }), className)} ref={ref} {...props} />
  ),
);

Button.displayName = "Button";
