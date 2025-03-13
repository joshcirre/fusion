export default function log(ctx, next) {
  const {action} = ctx;

  if (action.message) {
    console.log(action.message);
  }

  console.log(ctx);

  return next;
}