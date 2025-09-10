const puppeteer = require("puppeteer");
const fs = require("fs");
const path = require("path");
const { execSync } = require("child_process");

function delay(ms) {
  return new Promise(resolve => setTimeout(resolve, ms));
}

(async () => {
    
  const [, , outputFile, imageFile, shape] = process.argv;
  const framesDir = path.join(__dirname, "frames");
  // if (!fs.existsSync(framesDir)) {
  //   fs.mkdirSync(framesDir);
  // }

  if(shape == 'circle'){
    var jsonFile = "Profile_pic_circle.json";
  } else {
    var jsonFile = "Profile_pic_square.json";
  }

  // load JSON and replace image with base64
  const defaultGIF = `output-${Date.now()}.gif`;
  const defaultFrames = `frame-${Date.now()}-`;
  const animationData = JSON.parse(fs.readFileSync(jsonFile, "utf8"));
  // const imagePath = imageFile;
  // const imagePath = path.resolve(__dirname, imageFile);
  let imagePath = path.resolve(__dirname, '..');
  let outputGifPath = path.join(imagePath, outputFile);
  
  const ext = path.extname(imagePath).slice(1);
  imagePath = path.join(imagePath, imageFile);
  // console.log(imagePath);
  
  // return;
  const base64Image = fs.readFileSync(imagePath).toString("base64");
  // const dataUri = `data:image/png;base64,${base64Image}`;
  const dataUri = `data:image/${ext};base64,${base64Image}`;

  animationData.assets.forEach((asset) => {
    asset.u = "";
    asset.p = dataUri;
    asset.e = 1;
  });

  // const browser = await puppeteer.launch({ headless: true, args: ["--no-sandbox"] });
  const browser = await puppeteer.launch({
    headless: true,
    executablePath: "/home/customesignature-betaapp/.cache/puppeteer/chrome/linux-139.0.7258.154/chrome-linux64/chrome",
    args: ["--no-sandbox", "--disable-setuid-sandbox"]
  });
  const page = await browser.newPage();

  const animJson = JSON.stringify(animationData);

  await page.setContent(`
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8" />
      <script src="https://cdnjs.cloudflare.com/ajax/libs/bodymovin/5.12.1/lottie.min.js"></script>
    </head>
    <body style="margin:0; background:transparent; overflow:hidden;">
      <div id="anim" style="width:300px;height:300px;"></div>
      <script>
        const animationData = ${animJson};
        window.anim = lottie.loadAnimation({
          container: document.getElementById("anim"),
          renderer: "canvas",
          loop: false,
          autoplay: false,
          animationData: animationData
        });
      </script>
    </body>
    </html>
  `);

  // wait until animation is ready
  await page.waitForFunction("window.anim !== undefined");

  const totalFrames = await page.evaluate(() => window.anim.totalFrames);
  console.log("Total Frames:", totalFrames);

  for (let i = 0; i < totalFrames; i++) {
    await page.evaluate((frame) => window.anim.goToAndStop(frame, true), i);
    await delay(30); // wait for canvas paint
    await page.screenshot({
      path: path.join(framesDir, `${defaultFrames}_${String(i).padStart(4, "0")}.png`),
      clip: { x: 0, y: 0, width: 300, height: 300 },
      omitBackground: true,
    });
  }

  await browser.close();

	// Method 1 quality: best quality, less banding, larger file
  console.log("Creating GIF...");
  // execSync(`
  //   ffmpeg -y -framerate 24 -i ${framesDir}/frame_%04d.png \
  //   -vf "scale=300:-1:flags=lanczos,palettegen=reserve_transparent=1" \
  //   ${framesDir}/palette.png
  // `);

  // execSync(`
  //   ffmpeg -y -framerate 24 -i ${framesDir}/frame_%04d.png -i ${framesDir}/palette.png \
  //   -lavfi "scale=300:-1:flags=lanczos [x]; [x][1:v] paletteuse" \
  //   -gifflags -offsetting -loop 0 output.gif
  // `);


	// Method 2 quality: medium quality, more banding, smaller file
	execSync(`
    ffmpeg -y -framerate 24 -i ${framesDir}/${defaultFrames}_%04d.png \
    -vf "scale=300:-1:flags=lanczos,split[s0][s1];[s0]palettegen=reserve_transparent=1:stats_mode=diff[p];[s1][p]paletteuse=dither=bayer:bayer_scale=5" \
    -gifflags -offsetting -loop 0 phpoutput/${defaultGIF}
  `);


	// Method 3 quality: lower quality, more banding, smaller file
	// Generate palette + gif (no color cut)
	// execSync(`
	// 	ffmpeg -y -framerate 24 -i ${framesDir}/frame_%04d.png \
	// 	-vf "scale=300:-1 :flags=lanczos,palettegen=reserve_transparent=1" \
	// 	${framesDir}/palette.png
	// `);

	// execSync(`
	// 	ffmpeg -y -framerate 24 -i ${framesDir}/frame_%04d.png -i ${framesDir}/palette.png \
	// 	-lavfi "scale=300:-1:flags=lanczos [x]; [x][1:v] paletteuse=dither=sierra2_4a" \
	// 	-gifflags -offsetting -loop 0 output.gif
	// `);

	// Compress further with gifsicle
	execSync(`gifsicle -O3 --lossy=2 phpoutput/${defaultGIF} -o ${outputGifPath}`);

  // const file = "2690-218-circle.gif";
  fs.chmodSync(outputGifPath, 0o777);
  console.log(`✅ GIF saved as ${outputGifPath}`);


  // 🧹 Cleanup: delete frames + intermediate GIF
  fs.readdirSync(framesDir).forEach(file => {
    if (file.startsWith(defaultFrames)) {
      fs.unlinkSync(path.join(framesDir, file));
    }
  });
  const tempGif = path.join(__dirname, "phpoutput", defaultGIF);
  if (fs.existsSync(tempGif)) {
    fs.unlinkSync(tempGif);
  }
  console.log("🧹 Cleanup done (frames + temp GIF removed).");
})();
